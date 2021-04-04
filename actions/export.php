<?php

class Export
{
    public static function doSqlDump(string $pathForDumpedFile): ?string
    {
        $mysqlDumpBinary = exec('which mysqldump');

        $command = sprintf('%s --host="%s" --port="%s" --user="%s" --password="%s" "%s" > "%s"',
            $mysqlDumpBinary,
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            $_ENV['DB_NAME'],
            $pathForDumpedFile
        );

        exec($command);

        $filesize = filesize($pathForDumpedFile) / 8;

        if (file_exists($pathForDumpedFile) && $filesize > 0)
        {
            Output::log(sprintf('Dumping... %s to %s (%d ko)', $_ENV['DB_NAME'], $pathForDumpedFile, $filesize));

            return $pathForDumpedFile;
        }

        return null;
    }

    public static function doArchive(string $pathToZip, string $pathForZippedFile): ?string
    {
        $zipBinaryFile = exec('which zip');

        if (!file_exists($pathToZip)) {
            throw new Exception(sprintf('Error, path to archive does not exist: %s', $pathToZip));
        }

        $command = "$zipBinaryFile -r $pathForZippedFile $pathToZip";

        exec($command);

        $filesize = filesize($pathForZippedFile) / 8;

        if (file_exists($pathForZippedFile) && $filesize > 0)
        {
            Output::log(sprintf('Archiving... %s to %s (%d ko)', $pathToZip, $pathForZippedFile, $filesize));

            return $pathForZippedFile;
        }

        return null;
    }

}
