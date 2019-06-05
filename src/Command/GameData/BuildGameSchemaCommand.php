<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\SaintCoinach\SchemaBuilder;
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

    private $schemaBuilder;

    public function __construct(SchemaBuilder $schemaBuilder, $name = null)
    {
        $this->schemaBuilder = $schemaBuilder;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->schemaBuilder->build();
    }
}
