# PHP Google Drive backup

A simple CLI tool that I used to backud my www directory and my database from OVH to Google Drive, in a cronjob.

## Getting started 🔧

You need to configure your Google Cloud Console account first.

* Create a new project and an OAuth from [quickstart](https://developers.google.com/drive/api/v3/quickstart/php#step_1_turn_on_the).
  * Download the `credentials.json`.
* Activate [Google Drive API](https://console.cloud.google.com/apis/credentials/consent/edit?folder=&hl=fr&organizationId=&project=your-project).
  * Go to "OAuth authorization screen" and edit application.
  * Enable field `.../auth/drive` in order to create/remove some files.
    * I did enabled all the featured though, you must sync those from the web panel and those from the scopes in `actions/auth.php`).
* Add a test user with the email you wish to use.
* **Do not activate the "production review" since**.
  * You do not need it.
  * It will make you unable to continue the process.

## Developer resources 📝

* [Google Quickstart with Goole Drive API in PHP](https://developers.google.com/drive/api/v3/quickstart/php).
* Documentation & examples [googleapis/google-api-php-client](https://github.com/googleapis/google-api-php-client) (used by this project).
  * [API documentation](https://developers.google.com/resources/api-libraries/documentation/drive/v3/php/latest/index%2Ehtml) (for old school developers 💪).
  