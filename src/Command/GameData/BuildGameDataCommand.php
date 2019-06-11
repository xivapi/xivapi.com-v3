<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\GameData\GameBuilder;
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
    ];
    
    /** @var GameBuilder */
    private $gameBuilder;

    public function __construct(GameBuilder $gameBuilder, $name = null)
    {
        parent::__construct($name);
        
        $this->gameBuilder = $gameBuilder;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->gameBuilder
            ->extractCsvFilesToJson();
    }
}
