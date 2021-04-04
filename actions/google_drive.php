<?php

class GoogleDrive
{
    private Google_Service_Drive $service;

    /**
     * GoogleDrive constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @throws \Google\Exception
     * @see https://developers.google.com/drive/api/v3/quickstart/php
     */
    private function init(): void
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Drive API PHP Quickstart');
        $client->setScopes(Google_Service_Drive::DRIVE_FILE);
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        $this->service = new Google_Service_Drive($client);
    }

    /**
     * @param string $folderName the folder name to look for.
     * @return string uuid of the folder in Google Drive.
     * @see https://developers.google.com/drive/api/v3/reference/files/list
     */
    public function findFolder(string $folderName): ?string
    {
        if (!$folderName) {
            return null;
        }

        /** @var Google_Service_Drive_DriveFile[] $files */
        $files = $this->service->files->listFiles([
            'fields' => 'nextPageToken, files(id,name)',
            'q' => "name='$folderName' and mimeType='application/vnd.google-apps.folder'"
        ]);

        if (!empty($files)) {
            return $files[0]->getId();
        }

        return null;
    }

    /**
     * @param string $filepathToUpload
     * @param string $parentFolderId the parent folder id to use, see findFolder().
     * @throws Exception
     * @see https://developers.google.com/drive/api/v3/reference/files/create
     */
    public function upload(string $filepathToUpload, string $parentFolderId): void
    {
        if (!file_exists($filepathToUpload)) {
            throw new \Exception("File does not exist: $filepathToUpload");
        }

        Output::log("Uploading... $filepathToUpload\n");

        try {
            $optParams = [
                'name' => basename($filepathToUpload)
            ];

            if ($parentFolderId) {
                $optParams['parents'] = [$parentFolderId];
            }

            $file = new Google_Service_Drive_DriveFile($optParams);

            $request = $this->service->files->create($file, [
                'data' => file_get_contents($filepathToUpload),
                'mimeType' => mime_content_type($filepathToUpload),
                'uploadType' => 'media',
            ]);

            if ($request->getId()) {
                Output::log('Ok', Output::COLOR_SUCCESS);
            }

        } catch (Exception $e) {
            Output::log($e->getMessage(), Output::COLOR_ERROR);
        }
    }
}



