<?php

namespace App\Command\GameData;

use App\Command\Traits\CommandConfigureTrait;
use App\Service\SaintCoinach\SaintCoinach;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadSaintCoinachCommand extends Command
{
    use CommandConfigureTrait;

    const COMMAND = [
        'name' => 'xiv:download:saintcoinach',
        'desc' => 'Download the latest Saint Coinach from GitHub',
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
        $this->saintCoinach->download();
    }
}
