<?php

class GoogleDrive
{
    private Google_Service_Drive $service;
    private Google_Client $client;

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
        $this->client = $client;
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
            $optParams = [];

            if ($parentFolderId) {
                $optParams['parents'] = [$parentFolderId];
            }

            $file = new Google_Service_Drive_DriveFile($optParams);
            $file->setName(basename($filepathToUpload));

            $chunkSizeBytes = 1 * 1024 * 1024;

            $this->client->setDefer(true);

            $request = $this->service->files->create($file);

            // Create a media file upload to represent our upload process.
            $media = new Google\Http\MediaFileUpload(
                $this->client,
                $request,
                mime_content_type($filepathToUpload),
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($filepathToUpload));

            // Upload the various chunks. $status will be false until the process is
            // complete.
            $status = false;
            $handle = fopen($filepathToUpload, "rb");
            while (!$status && !feof($handle))
            {
                $chunk = $this->readFileChunck($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            // The final value of $status will be the data from the API for the object
            /** @var Google_Service_Drive_DriveFile $fileUploaded */
            $fileUploaded = false;
            if ($status != false) {
                $fileUploaded = $status;
            }

            fclose($handle);

            if ($id = $fileUploaded->getId()) {
                Output::log(sprintf('Ok %s', $id), Output::COLOR_SUCCESS);
            }

        } catch (Exception $e) {
            Output::log($e->getMessage(), Output::COLOR_ERROR);
        } finally {
            $this->client->setDefer(false);
        }
    }

    /**
     * @param $handle
     * @param $chunkSize
     * @return string
     * @see https://github.com/googleapis/google-api-php-client/blob/master/examples/large-file-upload.php#L133
     */
    private function readFileChunck($handle, $chunkSize)
    {
        $byteCount = 0;
        $giantChunk = "";
        while (!feof($handle)) {
            // fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
            $chunk = fread($handle, 8192);
            $byteCount += strlen($chunk);
            $giantChunk .= $chunk;
            if ($byteCount >= $chunkSize)
            {
                return $giantChunk;
            }
        }
        return $giantChunk;
    }
}



