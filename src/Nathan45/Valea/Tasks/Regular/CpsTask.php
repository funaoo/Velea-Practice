<?php

namespace Nathan45\Valea\Tasks\Regular;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;

class CpsTask extends PracticeTask
{
    public function __construct()
    {
        parent::__construct(self::TASK_CPS);
    }

    public function run(): void
    {
        $server       = Loader::getInstance()->getServer();
        $defaultWorld = $server->getWorldManager()->getDefaultWorld();

        foreach ($server->getOnlinePlayers() as $player) {
            if (!$player instanceof RPlayer) continue;

            if ($defaultWorld !== null && $player->getWorld()->getFolderName() === $defaultWorld->getFolderName()) {
                $player->setScoreTag(" ");
            } else {
                $player->setScoreTag(
                    str_repeat("§a|", (int) round($player->getHealth())) .
                    str_repeat("§c|", (int) round($player->getMaxHealth() - $player->getHealth()))
                );
            }
        }
    }

    public function end(): void
    {
    }

    public function getPeriod(): int
    {
        return 1;
    }
}