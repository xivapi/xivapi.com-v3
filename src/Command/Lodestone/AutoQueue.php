<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\LodestoneQueue\LodestoneAuto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoQueue extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'lodestone:queue',
        'desc' => 'Auto queue lodestone stuff',
    ];

    /** @var LodestoneAuto */
    private $lodestoneAuto;

    public function __construct(LodestoneAuto $lodestoneAuto, $name = null)
    {
        parent::__construct($name);

        $this->lodestoneAuto = $lodestoneAuto;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lodestoneAuto->queue();
    }
}
