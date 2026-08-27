<?php

namespace Nathan45\Valea\Utils;

use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Events\Event;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Utils\Interfaces\ICache;

class Cache implements ICache
{
    public array $boxing = [];

    private Loader $plugin;

    public array $ban = [];

    public array $requests = [];

    public array $players = [];

    public array $ffa = [];

    public array $duels_queue = [];

    public array $scoreboards = [];

    public array $freeze = [];

    public array $combats = [];

    public array $pearls = [];

    public array $events = [];

    public int $duelCount = 0;

    public array $fishing = [];

    public array $duels = [];

    private static self $instance;

    public function __construct()
    {
        self::$instance = $this;
        $this->plugin = Loader::getInstance();

        foreach ([
                     self::NODEBUFF,
                     self::GAPPLE,
                     self::FIST,
                     self::SUMO,
                     self::RUSH,
                     self::SOUP,
                     self::BOXING,
                     self::BU,
                     self::COMBO,
                 ] as $mode) {
            $this->ffa[$mode] = [];
        }

        foreach ([
                     self::GAPPLE,
                     self::NODEBUFF,
                     self::FIST,
                     self::SUMO,
                     self::FINAL,
                     self::CAVE,
                     self::BRIDGE,
                     self::SPLEEF,
                     self::BOXING,
                 ] as $mode) {
            $this->duels[$mode] = [];
        }
    }

    public function getNoDeBuff(): array
    {
        return $this->duels[self::NODEBUFF] ?? [];
    }

    public function getBuild(): array
    {
        return $this->duels[self::BU];
    }

    public function getGapple(): array
    {
        return $this->duels[self::GAPPLE] ?? [];
    }

    public function getFist(): array
    {
        return $this->duels[self::FIST] ?? [];
    }

    public function getBuFfa(): array
    {
        return $this->ffa[self::BU] ?? [];
    }

    public function getFinal(): array
    {
        return $this->duels[self::FINAL];
    }

    public function getCave(): array
    {
        return $this->duels[self::CAVE];
    }

    public function getBridge(): array
    {
        return $this->duels[self::BRIDGE];
    }

    public function getSpleef(): array
    {
        return $this->duels[self::SPLEEF];
    }

    public function getSumo(): array
    {
        return $this->duels[self::SUMO] ?? [];
    }

    public function getNoDeBuffFfa(): array
    {
        return $this->ffa[self::NODEBUFF] ?? [];
    }

    public function getBoxing(): array
    {
        return $this->duels[self::BOXING] ?? [];
    }

    public function removeNoDeBuffFFA(RPlayer $player): void
    {
        unset($this->ffa[self::NODEBUFF][array_search($player, $this->ffa[self::NODEBUFF], true)]);
    }

    public function getRushFfa(): array
    {
        return $this->ffa[self::RUSH] ?? [];
    }

    public function getComboFfa(): array
    {
        return $this->ffa[self::COMBO] ?? [];
    }

    public function removeRushFFA(RPlayer $player): void
    {
        unset($this->ffa[self::RUSH][array_search($player, $this->ffa[self::RUSH], true)]);
    }

    public function removeRush(RPlayer $player): void
    {
        unset($this->ffa[self::RUSH][array_search($player, $this->ffa[self::RUSH], true)]);
    }

    public function getGappleFfa(): array
    {
        return $this->ffa[self::GAPPLE] ?? [];
    }

    public function removeGappleFFA(RPlayer $player): void
    {
        unset($this->ffa[self::GAPPLE][array_search($player, $this->ffa[self::GAPPLE], true)]);
    }

    public function getFistFfa(): array
    {
        return $this->ffa[self::FIST] ?? [];
    }

    public function removeFistFFA(RPlayer $player): void
    {
        unset($this->ffa[self::FIST][array_search($player, $this->ffa[self::FIST], true)]);
    }

    public function getSumoFfa(): array
    {
        return $this->ffa[self::SUMO] ?? [];
    }

    public function removeSumoFFA(RPlayer $player): void
    {
        unset($this->ffa[self::SUMO][array_search($player, $this->ffa[self::SUMO], true)]);
    }

    public function getSoupFfa(): array
    {
        return $this->ffa[self::SOUP] ?? [];
    }

    public function removeSoupFFA(RPlayer $player): void
    {
        unset($this->ffa[self::SOUP][array_search($player, $this->ffa[self::SOUP], true)]);
    }

    public function getBoxingFfa(): array
    {
        return $this->ffa[self::BOXING] ?? [];
    }

    public function removeBoxingFFA(RPlayer $player): void
    {
        unset($this->ffa[self::BOXING][array_search($player, $this->ffa[self::BOXING], true)]);
    }

    public function setNoDeBuff(RPlayer $player): void
    {
        $this->addInDuel(self::NODEBUFF, $player);
    }

    public function setGapple(RPlayer $player): void
    {
        $this->addInDuel(self::GAPPLE, $player);
    }

    public function setSumo(RPlayer $player): void
    {
        $this->addInDuel(self::SUMO, $player);
    }

    public function setFist(RPlayer $player): void
    {
        $this->addInDuel(self::FIST, $player);
    }

    public function addInFfa(int $ffa, RPlayer $player): void
    {
        $this->ffa[$ffa][] = $player;
    }

    public function addInDuel(int $duel, RPlayer $player): void
    {
        $this->duels[$duel][] = $player;
    }

    public function setBan(array $ban): void
    {
        $this->ban = $ban;
    }

    public function setPlayers(array $players): void
    {
        $this->players = $players;
    }

    public function setCombats(array $combats): void
    {
        $this->combats = $combats;
    }

    public function setFreeze(array $freeze): void
    {
        $this->freeze = $freeze;
    }

    public function setScoreboards(array $scoreboards): void
    {
        $this->scoreboards = $scoreboards;
    }

    public function setDuels(array $duels): void
    {
        $this->duels = $duels;
    }

    public function getBan(): array
    {
        return $this->ban;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getCombats(): array
    {
        return $this->combats;
    }

    public function getFreeze(): array
    {
        return $this->freeze;
    }

    public function getScoreboards(): array
    {
        return $this->scoreboards;
    }

    public function getDuels(): array
    {
        return $this->duels;
    }

    public function getDuel(bool $ranked, int $players, string $mode): int
    {
        foreach ($this->getDuels() as $duel) {
            if (!$duel instanceof Duel) continue;
            if ($duel->getStatus() === Duel::STATUS_PENDING && $duel->isRanked() === $ranked && $duel->players === $players && strtolower($duel->getMode()) === strtolower($mode)) {
                return count($duel->getPlayers());
            }
        }
        return 0;
    }

    public function getRealDuel(bool $ranked, int $players, string $mode): ?Duel
    {
        foreach ($this->getDuels() as $duel) {
            if (!$duel instanceof Duel) continue;
            if ($duel->getStatus() === Duel::STATUS_PENDING && $duel->isRanked() === $ranked && $duel->players === $players && strtolower($duel->getMode()) === strtolower($mode)) {
                return $duel;
            }
        }
        return null;
    }

    public function getRankedDuels(bool $ranked = true): int
    {
        $count = 0;
        foreach ($this->getDuels() as $duel) {
            if (!$duel instanceof Duel) continue;
            if ($duel->isRanked() === $ranked) ++$count;
        }
        return $count;
    }

    public function getPlayersInDuel(bool $ranked = true, int $players = 2): int
    {
        $count = 0;
        foreach ($this->getDuels() as $duel) {
            if (!$duel instanceof Duel) continue;
            if ($duel->isRanked() === $ranked && $duel->players === $players) ++$count;
        }
        return $count;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }
}