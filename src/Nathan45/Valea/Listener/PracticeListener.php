<?php

namespace Nathan45\Valea\Listener;

use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Listener\PracticeEvents\BotDuelEndEvent;
use Nathan45\Valea\Listener\PracticeEvents\BoxingTapEvent;
use Nathan45\Valea\Listener\PracticeEvents\DuelCreateEvent;
use Nathan45\Valea\Listener\PracticeEvents\DuelEndEvent;
use Nathan45\Valea\Listener\PracticeEvents\FineTbRoundEvent;
use Nathan45\Valea\Listener\PracticeEvents\PlayerDeathInEventEvent;
use Nathan45\Valea\Listener\PracticeEvents\PlayerJoinFfaEvent;
use Nathan45\Valea\Listener\PracticeEvents\PlayerQuitFfaEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Tasks\Async\DeleteWorld;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Utils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class PracticeListener implements Listener, IUtils
{
    private Loader $plugin;
    private Utils $utils;
    private Cache $cache;

    public function __construct(Loader $main)
    {
        $this->utils  = new Utils();
        $this->plugin = $main;
        $this->cache  = Cache::getInstance();
    }

    public function onDuelEnd(DuelEndEvent $event): void
    {
        $losers  = $event->getLosers();
        $killers = $event->getWinners();
        $duel    = $event->getDuel();
        $level   = $duel->getLevel();

        $worldManager = $this->plugin->getServer()->getWorldManager();
        if (!$worldManager->isWorldLoaded(self::LOBBY_WORLD_NAME)) {
            $worldManager->loadWorld(self::LOBBY_WORLD_NAME);
        }

        foreach ($killers as $killer) {
            if (!$killer instanceof RPlayer) continue;
            $this->plugin->getServer()->dispatchCommand($killer, "spawn");
            if ($killer->getAllowedScoreboard()) {
                $this->cache->scoreboards[$killer->getName()] = new Scoreboard($killer, Scoreboard::SCOREBOARD_LOBBY);
            }
            if ($duel->isRanked()) {
                $elo = mt_rand(10, 15);
                $killer->addElo($elo);
                $killer->sendMessage(str_replace("{elo}", $elo, IMessages::RECEIVE_ELO));
                $killer->setDuel(null);
            }
        }

        foreach ($losers as $looser) {
            if (!$looser instanceof RPlayer) continue;
            if ($looser->getAllowedScoreboard()) {
                $this->cache->scoreboards[$looser->getName()] = new Scoreboard($looser, Scoreboard::SCOREBOARD_LOBBY);
            }
            $looser->setDuel(null);
        }

        $this->plugin->getServer()->getAsyncPool()->submitTask(
            new DeleteWorld($this->plugin->getServer()->getDataPath() . "worlds/" . $level->getFolderName(), $level)
        );
        $duel->delete();
    }

    public function onJoinFfa(PlayerJoinFfaEvent $event): void
    {
        $player = $event->getPlayer();
        $ffa    = $event->getFfa();

        $player->removeQueue();
        $player->sendMessage(self::PREFIX . "§aSuccessfully joined " . $ffa . " FFA, Good Luck !");

        if ($player->getAllowedScoreboard()) {
            $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_FFA);
        }

        switch (strtolower($ffa)) {
            case "boxing":
                if ($player->getAllowedScoreboard()) {
                    $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_BOXING);
                }
                break;

            case "sumo":
                $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 9999 * 100 * 20, 255, false));
                break;
        }
    }

    public function onDuelCreate(DuelCreateEvent $event): void
    {
        $duel    = $event->getDuel();
        $players = $duel->getPlayers();

        foreach ($players as $player) {
            if (!$player instanceof RPlayer) continue;

            if (strtolower($duel->getMode()) === "boxing") {
                if ($player->getAllowedScoreboard()) {
                    $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_BOXING);
                }
            } else {
                if ($player->getAllowedScoreboard()) {
                    $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_DUEL);
                }
                if (strtolower($duel->getMode()) === "sumo") {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 9999 * 100 * 20, 255, false));
                }
            }
        }

        $p1 = $duel->getPlayers()[0];
        $p2 = $duel->getPlayers()[1];
        $p1->sendTitle(TextFormat::RED . "Starting...", TextFormat::WHITE . "Opponent: " . $p2->getName());
        $p2->sendTitle(TextFormat::RED . "Starting...", TextFormat::WHITE . "Opponent: " . $p1->getName());
    }

    public function onQuitFfa(PlayerQuitFfaEvent $event): void
    {
        $player = $event->getPlayer();
        $this->cache->{"remove" . $event->getFfa() . "FFA"}($player);
        if ($player->getAllowedScoreboard()) {
            $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_LOBBY);
        }
        $player->getEffects()->clear();
    }

    public function onTap(BoxingTapEvent $event): void
    {
        $damager = $event->getDamager();
        $victim  = $event->getVictim();

        if (!isset($this->cache->boxing[$damager->getName()])) {
            $this->cache->boxing[$damager->getName()] = 0;
        }

        $damager->incrementHit();

        if ($damager->getHit() === IUtils::BOXING_HIT_TO_WIN) {
            $this->utils->fineBoxing($damager, $victim);
        }
    }

    public function onBotDuelEnd(BotDuelEndEvent $event): void
    {
        $bot    = $event->getBot();
        $player = $event->getPlayer();

        if ($bot instanceof Bot) {
            $bot->flagForDespawn();
        }

        if ($player->getAllowedScoreboard()) {
            $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_LOBBY);
        }

        $world = $player->getWorld();
        $this->plugin->getServer()->getAsyncPool()->submitTask(
            new DeleteWorld($this->plugin->getServer()->getDataPath() . "worlds/" . $world->getFolderName(), $world)
        );
        $this->plugin->getServer()->dispatchCommand($player, "spawn");
    }

    public function onDeathInEvent(PlayerDeathInEventEvent $event): void
    {
        $player = $event->getPlayer();
        $ev     = $event->getEvent();
        $killer = $event->getKiller();

        $player->teleport($ev->getBaseLocation());
        $ev->broadcastMessage("§a{$player->getName()} §6lost to §a{$killer->getName()}.");
        $ev->hasEliminated($player, $killer);
        $ev->winRound($killer);
        $ev->setNewRound();
    }

    public function onFineTbRound(FineTbRoundEvent $event): void
    {
        $looser = $event->getLooser();
        $winner = $event->getWinner();
        $duel   = $event->getDuel();
        $round  = $event->getRound();

        if ($round === IUtils::TB_ROUND_FOR_WIN) {
            $team = $duel->getTeamFor($winner);
            (new DuelEndEvent($duel, $duel->{"getTeam" . $team}(), $duel->{"getTeam" . $team}()))->call();
            return;
        }

        $duel->addRound();
        $duel->addWonRoundFor($winner->getName());
        $looser->teleport($duel->getTp1());
        $winner->teleport($duel->getTp2());
        $duel->broadcastMessage(
            IUtils::PREFIX . "§a" . $winner->getName() . " §ron round §b{$round} §ragainst §c{$looser->getName()}. §7(§b" .
            $duel->getWonRoundsBy($winner->getName()) . "§a-§c" . $duel->getWonRoundsBy($looser->getName()) . "§7)."
        );
    }
}