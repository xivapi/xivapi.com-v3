<?php

namespace App\Service\SaintCoinach;

use App\Utils\Downloader;
use Github\Api\Repo;
use Github\Client;

class SaintCoinach
{
    const GAME_TOOLS          = ROOT . '/data/game_tools';
    const GAME_SCHEMA         = ROOT . '/data/game_schema';
    const GAME_VERSION        = ROOT . '/data/game_schema/game.ver';
    const GAME_DATA           = ROOT . '/data/raw-exd-all';
    const GAME_DATA_JSON      = ROOT . '/data/raw-exd-all-json';
    const GAME_INSTALL_PATH   = 'C:\Program Files (x86)\SquareEnix\FINAL FANTASY XIV - A Realm Reborn';
    const GITHUB_USERNAME     = 'ufx';
    const GITHUB_REPO         = 'SaintCoinach';
    const GITHUB_PATH         = '/SaintCoinach/Definitions';

    /**
     * Downloads all the latest schema definitions
     */
    public function downloadLatestSchemaDefinitions()
    {
        $client = new Client();

        /** @var Repo $repo */
        $repo = $client->api('repo');

        // grab the latest release
        $files = $repo->contents()->show(self::GITHUB_USERNAME, self::GITHUB_REPO, self::GITHUB_PATH);

        if (empty($files)) {
            throw new \Exception("The definition directory was empty when attempting to download the latest SaintCoinach Schemas");
        }

        // download each schema file individually
        $combined = [];
        foreach ($files as $file) {
            $name = $file['name'];
            $url  = $file['download_url'];

            Downloader::save($url, self::GAME_SCHEMA ."/{$name}");
            $combined[$name] = json_decode(file_get_contents($url), true);
        }

        // get the game version
        $version = trim(file_get_contents(self::GAME_VERSION));

        // save game version
        file_put_contents(self::GAME_SCHEMA ."/ex.{$version}.json", json_encode($combined, JSON_PRETTY_PRINT));
    }

    /**
     * Downloads the latest SaintCoinach releases
     */
    public function downloadLatestSaintTools()
    {
        $client = new Client();

        /** @var Repo $repo */
        $repo = $client->api('repo');

        // get latest release
        $files = $repo->releases()->latest(self::GITHUB_USERNAME, self::GITHUB_REPO);

        // get release version
        $version = $files['tag_name'];

        // download each assets
        foreach ($files['assets'] as $files) {
            $name = $files['name'];
            $uri  = $files['browser_download_url'];

            // save filename
            $save    = self::GAME_TOOLS ."/{$version}/{$name}";

            $extract = str_ireplace('.zip', null, $save);

            // download
            Downloader::save($uri, $save);

            // extract it
            $zip = new \ZipArchive;
            $zip->open($save);
            $zip->extractTo($extract);
            $zip->close();
        }
    }
}
