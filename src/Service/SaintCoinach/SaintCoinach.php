<?php

namespace App\Service\SaintCoinach;

use App\Utils\Downloader;
use Github\Client;
use Symfony\Component\Console\Output\ConsoleOutput;

class SaintCoinach
{
    const DATA_STORAGE_PATH  = ROOT . '/tools/';
    const SAINT_EX_FILENAME  = ROOT . '/tools/SaintCoinach.Cmd/ex.json';
    const SAINT_DIRECTORY    = ROOT . '/tools/SaintCoinach.Cmd';
    const GAME_INSTALL_PATH  = 'C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn';

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
        $this->console->writeln('Getting latest build from github ...');
        $release  = (new Client())->api('repo')->releases()->latest('ufx', 'SaintCoinach');
        $buildTag = $release['tag_name'];
        $this->console->writeln("Latest build: <info>{$buildTag}</info>");

        // check for SaintCoinach.Cmd release
        $build = $release['assets'][1] ?? false;
        if ($build === false) {
            throw new \Exception('Could not find SaintCoinach.Cmd release at Download Position 1');
        }
        
        // make data storage path if it does not exist
        $this->console->writeln('Checking data directory');
        if (is_dir(self::DATA_STORAGE_PATH) == false) {
            mkdir(self::DATA_STORAGE_PATH);
        }

        // Download
        $download = $build['browser_download_url'];
        $filename = self::DATA_STORAGE_PATH . 'SaintCoinach.Cmd.zip';
        $this->console->writeln("Downloading: <info>{$download}</info>");
        $this->console->writeln("Save Path: <info>{$filename}</info>");

        Downloader::save($download, $filename);

        // extract it
        $this->console->writeln("Extracting: <info>{$filename}</info>");
        $extractFolder = self::DATA_STORAGE_PATH . 'SaintCoinach.Cmd';

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
        if (!file_exists(self::SAINT_EX_FILENAME)) {
            throw new \Exception("SaintCoinach schema ex.json file missing at: ". self::SAINT_EX_FILENAME);
        }

        $schema = \GuzzleHttp\json_decode(
            file_get_contents(self::SAINT_EX_FILENAME)
        );

        return $schema;
    }

    /**
     * Get the current extracted schema version, this is
     * the version from the folder, not the one in ex.json
     */
    public function version()
    {
        $dirs = glob(self::SAINT_DIRECTORY . '/*' , GLOB_ONLYDIR);

        // there should only be 1, if not, throw exception to sort this
        if (count($dirs) > 1) {
            throw new \Exception("there is more than 1 directory in the SaintCoinach extracted location, delete old extractions");
        }

        return str_ireplace([self::SAINT_DIRECTORY, '/'], null, $dirs[0]);
    }

    /**
     * Return the data directory for where stuff is extracted
     */
    public function directory()
    {
        return self::SAINT_DIRECTORY ."/". $this->version();
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
                self::GAME_INSTALL_PATH,
                $command
            )
        );
    }
}
