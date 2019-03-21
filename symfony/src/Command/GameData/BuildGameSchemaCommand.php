<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\SaintCoinach\SaintCoinachSchemaGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildGameSchemaCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'xiv:build:schema',
        'desc' => 'Build the FFXIV Game Schema',
    ];

    private $saintCoinachSchemaGenerator;

    public function __construct(SaintCoinachSchemaGenerator $saintCoinachSchemaGenerator, $name = null)
    {
        $this->saintCoinachSchemaGenerator = $saintCoinachSchemaGenerator;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->saintCoinachSchemaGenerator->build();
    }
}
