<?php

namespace App\Command;

use App\Service\SaintCoinach\SaintCoinach;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaintCoinachCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'SaintCoinachCommand',
        'desc' => 'Saint Coinach Stuff',
        'args' => [
            [ 'action', InputArgument::REQUIRED, 'What action would you like to perform?'  ]
        ]
    ];

    /** @var SaintCoinach */
    private $saintCoinach;
    
    public function __construct(SaintCoinach $saintCoinach, $name = null)
    {
        $this->saintCoinach = $saintCoinach;
        
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');
        $output->writeln(__CLASS__ ." - Action: {$action}");

        switch($action) {
            default:
                $output->writeln("Unknown action: {$action}");
                break;

            case 'download_schema':
                $output->writeln("Downloading all schema definitions (this may take some time)...");
                $this->saintCoinach->downloadLatestSchemaDefinitions();
                $output->writeln("Finished!");
                break;

            case 'download_tools':
                $output->writeln("Downloading the latest SaintCoinach and Godbert");
                $this->saintCoinach->downloadLatestSaintTools();
                $output->writeln("Finished!");
                break;
        }
    }
}
