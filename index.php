<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

require "actions/export.php";
require "actions/output.php";
require "actions/google_drive.php";

$timestamp = date("Y-m-d_H-i-s", time());
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

Output::log('Make sure you read the readme.md first.', Output::COLOR_WARNING);

$ENVS = [
    "DB_HOST",
    "DB_USER",
    "DB_PASSWORD",
    "DB_NAME",
    "DB_PORT",
    "DIRECTORY_TO_BACKUP",
    "DELETE_UPLOADED_BACKUPS",
    "ONLY_CLI",
//    "GDRIVE_DESTINATION_FOLDER", not mandatory
];

foreach ($ENVS as $ENV) {
    if (strpos($_ENV['SYMFONY_DOTENV_VARS'], $ENV) === false) {
        throw new Exception(sprintf('Your env variables should be configured for %s.', $ENV));
    }
}

if ($_ENV['ONLY_CLI'] === "true" && php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

$drive = new GoogleDrive();

$parentFolderId = $drive->findFolder($_ENV['GDRIVE_DESTINATION_FOLDER']);
if (!$parentFolderId)
{
    Output::log('No folder selected for upload, using root folder :)', Output::COLOR_INFO);
}

$pathForZippedFile = "backups/www_$timestamp.zip";
$zippedWebDir = Export::doArchive($_ENV['DIRECTORY_TO_BACKUP'], $pathForZippedFile);
if ($zippedWebDir) {
    $drive->upload($zippedWebDir, $parentFolderId);
} else {
    Output::log("No file named: $pathForZippedFile", Output::COLOR_ERROR);
}

echo "\n";

$pathForDumpedFile = "backups/sql_$timestamp.sql";
$dumpedDatabase = Export::doSqlDump($pathForDumpedFile);
if ($dumpedDatabase) {
    $drive->upload($dumpedDatabase, $parentFolderId);
} else {
    Output::log("No content in: $pathForDumpedFile", Output::COLOR_ERROR);
}

echo "\n";

if ($_ENV['DELETE_UPLOADED_BACKUPS'] === "true") {
    Output::log(exec("[ -f $pathForDumpedFile ] && rm -v $pathForDumpedFile") . "\n", Output::COLOR_INFO);
    Output::log(exec("[ -f $pathForZippedFile ] && rm -v $pathForZippedFile") . "\n\n", Output::COLOR_INFO);
}

Output::log('You can contribute or open an issue here: https://github.com/alexsoyes/php-google-drive-backup', Output::COLOR_INFO);
