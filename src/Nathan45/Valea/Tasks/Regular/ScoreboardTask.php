<?php

namespace Nathan45\Valea\Tasks\Regular;

use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Listener\PracticeEvents\DuelEndEvent;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Tasks\Async\MotdAsyncTask;

class ScoreboardTask extends PracticeTask
{
    public function __construct()
    {
        parent::__construct(self::TASK_SCOREBOARD);
    }

    public function run(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if (!$player instanceof RPlayer) continue;
            if (!$player->isConnected()) continue;

            $player->getScoreboard();

            if ($player->getAllowedScoreboard()) {
                $this->cache->scoreboards[$player->getName()]->update();
            }

            $duelInQueue = $player->isInQueue();
            if ($duelInQueue instanceof Duel) {
                $player->sendTip(
                    "§8Waiting for players > §6" . date("h:i:s", $duelInQueue->getWaitingTimeFor($player)) .
                    "\n§6" . $duelInQueue->getMode() . " " . $duelInQueue->players / 2 . "v" . $duelInQueue->players / 2 .
                    " " . ($duelInQueue->isRanked() ? "ranked" : "unranked")
                );
            }

            $activeDuel = $player->getDuel();
            if ($activeDuel instanceof Duel && count($activeDuel->getLevel()->getPlayers()) === 1) {
                (new DuelEndEvent($activeDuel, [$player], []))->call();
            }

            if ($player->getPearlCooldown() === "§aAvailable") {
                $player->getXpManager()->setCurrentTotalXp(0);
            } else {
                $player->getXpManager()->setXpLevel((int) $player->getPearlCooldown());
            }

        }

        $this->plugin->getServer()->getAsyncPool()->submitTask(new MotdAsyncTask());
    }

    public function end(): void
    {
    }

    public function getPeriod(): int
    {
        return 10;
    }
}