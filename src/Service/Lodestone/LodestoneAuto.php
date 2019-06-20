<?php

namespace App\Service\LodestoneQueue;

use App\Entity\Character;
use App\Entity\CharacterAchievements;
use App\Entity\CharacterFriends;
use App\Entity\Entity;
use App\Entity\FreeCompany;
use App\Entity\Linkshell;
use App\Entity\PvPTeam;
use App\Repository\CharacterAchievementRepository;
use App\Repository\CharacterFriendsRepository;
use App\Repository\CharacterRepository;
use App\Repository\FreeCompanyRepository;
use App\Repository\LinkshellRepository;
use App\Repository\PvPTeamRepository;
use App\Service\Lodestone\LodestoneRequest;
use App\Service\Lodestone\ServiceQueues;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class LodestoneAuto
{
    const UPDATE_GROUPS = [
        [ Entity::PRIORITY_NORMAL, "normal" ],
        [ Entity::PRIORITY_PATRON, "patreon" ],
    ];

    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->console = new ConsoleOutput();
    }

    /**
     * Automatically queue stuff up to the lodestone rabbit message queue
     */
    public function queue()
    {
        // Don't queue anything during this time as the Supervisord service is restarted.
        if (in_array(date('i'), [ 30, 31 ])) {
            return;
        }

        // queue everything!
        $this->handle('character', ServiceQueues::TOTAL_CHARACTER_QUEUES, Character::class);
        $this->handle('character_friends', ServiceQueues::TOTAL_CHARACTER_FRIENDS_QUEUES, CharacterFriends::class);
        $this->handle('character_achievements', ServiceQueues::TOTAL_CHARACTER_ACHIEVEMENTS_QUEUES, CharacterAchievements::class);
        $this->handle('free_company', ServiceQueues::TOTAL_FC_QUEUES, FreeCompany::class);
        $this->handle('linkshell', ServiceQueues::TOTAL_LS_QUEUES, Linkshell::class);
        $this->handle('pvp_team', ServiceQueues::TOTAL_PVP_QUEUES, PvPTeam::class);
    }

    /**
     * handle the queue piping.
     */
    private function handle($action, $total, $class)
    {
        /** @var CharacterRepository $repo */
        $repo = $this->em->getRepository($class);

        [ $totalNormal, $totalPatreon ] = $total;

        foreach (self::UPDATE_GROUPS as $group) {
            [ $priority, $type ] = $group;

            // patreon and normal have different queue sizes
            $total = ($type === Entity::PRIORITY_PATRON) ? $totalPatreon: $totalNormal;

            foreach (range(0, $total) as $number) {
                $this->console->writeln("[{$number}] {$total} {$action} ({$priority} {$type})");
                LodestoneRequest::handle("{$action}_update_{$number}_{$type}", $action, $repo->getUpdateIds($priority, $number));
            }
        }
    }
}
