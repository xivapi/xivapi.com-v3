<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\SaintCoinach\SchemaDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadSaintSchemaCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'xiv:download:saintschema',
        'desc' => 'Download Saint Schemas for Official, Korean and Chinese',
    ];
    
    private $schemaDownloader;
    
    public function __construct(SchemaDownloader $schemaDownloader, $name = null)
    {
        $this->schemaDownloader = $schemaDownloader;
        
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->schemaDownloader->download();
    }
}
