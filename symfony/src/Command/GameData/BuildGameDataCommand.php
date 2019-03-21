<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
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

    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__METHOD__);
    }
}
