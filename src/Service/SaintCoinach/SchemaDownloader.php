<?php

namespace App\Service\SaintCoinach;

use Github\Api\Repo;
use Github\Client;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class SchemaDownloader
{
    const PATCH_FILENAME   = ROOT . '/data/GAME_PATCH_INFO.json';
    const SCHEMA_SAVE_PATH = ROOT . '/data/schema_[version].json';
    const SCHEMA_FILENAME  = 'https://raw.githubusercontent.com/ufx/SaintCoinach/[sha_hash]/SaintCoinach/ex.json';
    
    /** @var ConsoleOutput */
    private $console;
    
    public function __construct()
    {
        $this->console = new ConsoleOutput();
    }
    
    /**
     * Download the Saint Schema for each language
     */
    public function download()
    {
        $this->console->writeln("Downloading SaintCoinach Schemas");
        
        // grab patch info
        $patchdata = json_decode(
            file_get_contents(self::PATCH_FILENAME)
        );
    
        /** @var Repo $api */
        $api = (new Client())->api('repo');

        // find a JSON schema for each region
        foreach ($patchdata->GameVersions as $versionName => $version) {
            $releaseDate = date('F j, Y, g:i a', $version->ReleaseDate);
            $schemaDate  = date('c', $version->SchemaDate);

            if ($version->On === false) {
                continue;
            }

            // header
            $this->console->writeln([
                "",
                "-- Game Version: <info>{$version->Number} {$versionName} - {$releaseDate}</info> --",
            ]);
            
            // get commits before the schema data
            $commits = $api->commits()->all('ufx', 'SaintCoinach', [
                'sha'   => 'master',
                'path'  => 'SaintCoinach/ex.json',
                'until' => $schemaDate
            ]);
            
            if (empty($commits)) {
                throw new \Exception("There seems to be no commits before: {$schemaDate} ???");
            }
            
            // grab the 1st one
            $commit = $commits[0];
            
            // build downloadable url
            $downloadUrl = str_ireplace('[sha_hash]', $commit['sha'], self::SCHEMA_FILENAME);
            
            $table = new Table($this->console);
            $table->setHeaders([
                'Commit', 'Date', 'Message'
            ]);
            
            $table->setRows([
                [
                    "<comment>{$commit['sha']}</comment>",
                    $commit['commit']['committer']['date'],
                    $commit['commit']['message']
                ]
            ]);
            
            $table->render();
            
            // print some schema info
            $this->console->writeln("Download Url: <comment>{$downloadUrl}</comment>");
            
            // download the schema
            $saveSchema = str_ireplace('[version]', $versionName, self::SCHEMA_SAVE_PATH);
            file_put_contents($saveSchema, file_get_contents($downloadUrl));
            $this->console->writeln([
                "✔ Complete.",
                ""
            ]);
        }
        
        // finished
        $this->console->writeln([
            "All version schemas downloaded.",
            "They can be found in: ". ROOT . '/data',
            ""
        ]);
    }
}
