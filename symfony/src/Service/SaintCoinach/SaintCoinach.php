<?php

namespace App\Service\SaintCoinach;

use App\Utils\Downloader;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;

class SaintCoinach
{
    const SAVE_PATH        = __DIR__.'/data/';
    const SCHEMA_FILENAME  = __DIR__ . '/data/SaintCoinach.Cmd/ex.json';
    const SCHEMA_DIRECTORY = __DIR__ . '/data/SaintCoinach.Cmd';
    const DOCUMENTS_FOLDER = __DIR__ . '/data/documents/';
    const GAME_PATH        = 'C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn';

    /** @var ConsoleOutput */
    private $console;

    public function __construct()
    {
        $this->console = new ConsoleOutput();
    }

    /**
     * Download SaintCoinach
     */
    public function download()
    {
        $this->console->writeln('Downloading SaintCoinach.Cmd');

        // grab the latest release from github
        $release  = (new Client())->api('repo')->releases()->latest('ufx', 'SaintCoinach');
        $buildTag = $release['tag_name'];
        $this->console->writeln("Latest build: <info>{$buildTag}</info>");

        // check for SaintCoinach.Cmd release
        $build = $release['assets'][1] ?? false;
        if ($build === false) {
            throw new \Exception('Could not find Saint Coinach cmd release at Download Position 1');
        }

        // Download
        $download = $build['browser_download_url'];
        $filename = self::SAVE_PATH . 'SaintCoinach.Cmd.zip';
        $this->console->writeln("Downloading: <info>{$download}</info>");
        $this->console->writeln("Save Path: <info>{$filename}</info>");

        Downloader::save($download, $filename);

        // extract it
        $this->console->writeln("Extracting: <info>{$filename}</info>");
        $extractFolder = self::SAVE_PATH . 'SaintCoinach.Cmd';

        $zip = new \ZipArchive;
        $zip->open($filename);
        $zip->extractTo($extractFolder);
        $zip->close();

        $this->console->writeln('Generating Bat Scripts');
        $this->writeBatchScript($extractFolder, 'allrawexd');
        $this->writeBatchScript($extractFolder, 'ui');
        $this->writeBatchScript($extractFolder, 'bgm');
        $this->writeBatchScript($extractFolder, 'maps');
        $this->console->writeln('Finished');
    }

    /**
     * Request the schema
     */
    public function schema()
    {
        if (!file_exists(self::SCHEMA_FILENAME)) {
            throw new \Exception("SaintCoinach schema ex.json file missing at: ". self::SCHEMA_FILENAME);
        }

        $schema = \GuzzleHttp\json_decode(
            file_get_contents(self::SCHEMA_FILENAME)
        );

        return $schema;
    }

    /**
     * Get the current extracted schema version, this is
     * the version from the folder, not the one in ex.json
     */
    public function version()
    {
        $dirs = glob(self::SCHEMA_DIRECTORY . '/*' , GLOB_ONLYDIR);

        // there should only be 1, if not, throw exception to sort this
        if (count($dirs) > 1) {
            throw new \Exception("there is more than 1 directory in the SaintCoinach extracted location, delete old extractions");
        }

        return str_ireplace([self::SCHEMA_DIRECTORY, '/'], null, $dirs[0]);
    }

    /**
     * Return the data directory for where stuff is extracted
     */
    public function directory()
    {
        return self::SCHEMA_DIRECTORY ."/". $this->version();
    }

    /**
     * Generate a windows bat script that runs a command via SaintCoinach.Cmd
     */
    private function writeBatchScript($extractFolder, $command)
    {
        file_put_contents(
            "{$extractFolder}/extract-{$command}.bat",
            sprintf(
                'SaintCoinach.Cmd.exe "%s" %s /UseDefinitionVersion',
                self::GAME_PATH,
                $command
            )
        );
    }

}
