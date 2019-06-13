<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\GameData\CSVManager;
use App\Service\GameData\DocumentBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildGameDataCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'xiv:build:gamedata',
        'desc' => 'Build the FFXIV Game Data',
        'args' => [
            [ 'action', InputArgument::REQUIRED, 'Action to perform on the game data']
        ]
    ];
    
    /** @var CSVManager */
    private $csv;
    /** @var DocumentBuilder */
    private $documents;

    public function __construct(CSVManager $csv, DocumentBuilder $documents, $name = null)
    {
        parent::__construct($name);
        
        $this->csv = $csv;
        $this->documents = $documents;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch (strtolower($input->getArgument('action'))) {
            case 'csv':
                $this->csv->extractCsvFilesToJson();
                break;
                
            case 'documents':
                $this->documents->buildGameDocuments();
                break;
        }
    }
}
