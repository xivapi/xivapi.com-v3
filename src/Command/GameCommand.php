<?php

namespace App\Command;

use App\Command\CommandConfigureTrait;
use App\Service\GameData\CSVManager;
use App\Service\GameData\DocumentBuilder;
use App\Service\SaintCoinach\SchemaBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GameCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'GameCommand',
        'desc' => 'Do shit with the game data',
        'args' => [
            [ 'action', InputArgument::REQUIRED, 'Action to perform on the game data']
        ]
    ];
    
    /** @var CSVManager */
    private $csv;
    /** @var DocumentBuilder */
    private $documents;
    /** @var SchemaBuilder */
    private $schemaBuilder;

    public function __construct(
        CSVManager $csv,
        DocumentBuilder $documents,
        SchemaBuilder $schemaBuilder,
        $name = null
    ) {
        parent::__construct($name);
        
        $this->csv = $csv;
        $this->documents = $documents;
        $this->schemaBuilder = $schemaBuilder;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        $output->writeln(__CLASS__ ." - Action: {$action}");

        switch ($action) {
            default:
                $output->writeln("Unknown action: {$action}");
                break;

            case 'build_csv_to_json':
                $this->csv->extractCsvFilesToJson();
                break;
                
            case 'build_game_documents':
                $this->documents->buildGameDocuments();
                break;

            case 'build_schema':
                $this->schemaBuilder->build();
                break;
        }
    }
}
