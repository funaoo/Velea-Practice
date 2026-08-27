<?php

namespace Nathan45\Valea\Events;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Tasks\Async\CreateWorld;
use Nathan45\Valea\Tasks\Async\DeleteWorld;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\math\Vector3;

class Event
{
    const PREFIX = "§l§cEvent > §r";
    const TIME_REMAINING_BEFORE_START = 180;

    private array $players = [];
    private int $timeRemaining;
    private Cache $cache;
    private Loader $plugin;
    private int $round = 0;
    private array $fighters = [];
    private array $winners = [];
    private int $phase = 1;
    private int $unique_id;

    public function __construct(private RPlayer $hoster, private string $type, private bool $private = false, private string|null $password = null)
    {
        $this->timeRemaining = self::TIME_REMAINING_BEFORE_START + time();
        $this->cache = Cache::getInstance();
        $this->plugin = Loader::getInstance();
        $this->unique_id = ++$this->cache->duelCount;
        $this->plugin->getServer()->getAsyncPool()->submitTask(new CreateWorld(true, $this->type . "event" . $this->unique_id, $this->plugin->getServer()->getDataPath(), IUtils::NODEBUFF_EVENT_WORLD_NAME));
        $this->setSpawnLocation();
    }

    public function setSpawnLocation(): void
    {
        $this->getLevel()?->setSpawnLocation(match ($this->type) {
            "gapple"   => new Vector3(IUtils::EVENT_GAPPLE_X, IUtils::EVENT_GAPPLE_Y, IUtils::EVENT_GAPPLE_Z),
            "nodebuff" => new Vector3(IUtils::EVENT_NODEBUFF_X, IUtils::EVENT_NODEBUFF_Y, IUtils::EVENT_NODEBUFF_Z),
            default    => new Vector3(IUtils::EVENT_SUMO_X, IUtils::EVENT_SUMO_Y, IUtils::EVENT_SUMO_Z)
        });
    }

    public function getHoster(): RPlayer
    {
        return $this->hoster;
    }

    public function getName(): string
    {
        return $this->getHoster()->getName();
    }

    public function getTp1(): Vector3
    {
        return match (strtolower($this->type)) {
            "sumo"  => new Vector3(320, 81, 303),
            default => new Vector3(320, 81, 303),
        };
    }

    public function getTp2(): Vector3
    {
        return match (strtolower($this->type)) {
            "sumo"  => new Vector3(320, 81, 315),
            default => new Vector3(320, 81, 315),
        };
    }

    public function getBaseLocation(): Position
    {
        $this->setSpawnLocation();
        return $this->getLevel()->getSafeSpawn();
    }

    public function getTimeRemaining(): int
    {
        return $this->timeRemaining - time();
    }

    public function hasStarted(): bool
    {
        return ($this->timeRemaining - time()) <= 0;
    }

    public function addPlayer(RPlayer $player): void
    {
        if (count($this->players) >= 32) {
            $player->sendMessage(IUtils::PREFIX . "§cSorry, this event is full :/");
            return;
        }

        $this->players[] = $player;
        $player->teleport($this->getPosition());
        $player->sendMessage(IUtils::PREFIX . "§aSuccessfully joined the event!");

        if ($player->getAllowedScoreboard()) {
            $this->cache->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_EVENT_REMAINING, null, null, $this);
        }
    }

    public function getFighter1(): ?RPlayer
    {
        return $this->fighters[0] ?? null;
    }

    public function getFighter2(): ?RPlayer
    {
        return $this->fighters[1] ?? null;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getLevel(): ?World
    {
        $levelName = $this->getType() . "event" . $this->unique_id;
        $worldManager = $this->plugin->getServer()->getWorldManager();
        $worldManager->loadWorld($levelName);
        return $worldManager->getWorldByName($levelName);
    }

    public function setStart(bool $start = true): void
    {
        shuffle($this->players);

        if (count($this->players) <= 1) {
            $this->getHoster()->sendMessage(IUtils::PREFIX . "You're alone in your event!");
            return;
        }

        ++$this->round;
        $this->setNewRound();
    }

    public function getMaxRounds(): int
    {
        return (int) ceil(count($this->players) / 2);
    }

    public function getPhase(): int
    {
        return $this->phase;
    }

    public function addPhase(): void
    {
        ++$this->phase;

        if ($this->phase > $this->getMaxPhases()) {
            $this->endEvent($this->winners[array_key_first($this->winners)]);
            return;
        }

        $previousPhase = $this->phase - 1;
        $this->broadcastMessage("§6Phase {$previousPhase} is over, we are starting phase {$this->phase}");
        $this->winners = [];
    }

    public function getMaxPhases(): int
    {
        $int = count($this->players);

        if ($int > 16) return 5;
        if ($int > 8)  return 4;
        if ($int > 4)  return 3;
        if ($int > 2)  return 2;

        return 1;
    }

    public function getPosition(): ?Position
    {
        $this->setSpawnLocation();
        return $this->getLevel()?->getSafeSpawn();
    }

    public function setNewRound(): void
    {
        $this->fighters = [];

        $p1 = $this->players[($this->round * 2) - 2] ?? null;
        $p2 = $this->players[($this->round * 2) - 1] ?? null;

        foreach ([$p1, $p2] as $key => $player) {
            if (!$player instanceof RPlayer) {
                if ($player !== null) {
                    unset($this->players[array_search($player, $this->players, true)]);
                }
                $this->setNewRound();
                return;
            }

            if (in_array($player, $this->winners, true)) {
                $this->setNewRound();
                return;
            }

            $player->reKit($this->type);
            $this->fighters[] = $player;
            $tpMethod = "getTp" . ($key + 1);
            $player->teleport(new Position($this->{$tpMethod}()->x, $this->{$tpMethod}()->y, $this->{$tpMethod}()->z, $this->getLevel()));
        }

        $this->broadcastMessage("§6A new round started between {$p2->getName()} and {$p1->getName()}");
    }

    public function broadcastMessage(string $message): void
    {
        foreach ($this->players as $player) {
            if ($player instanceof RPlayer) {
                $player->sendMessage(self::PREFIX . $message);
            }
        }
    }

    public function hasEliminated(RPlayer $player, RPlayer $killer): void
    {
        unset($this->players[array_search($player, $this->players, true)]);
        $this->plugin->getServer()->dispatchCommand($player, "spawn");
    }

    public function winRound(RPlayer $player): void
    {
        $player->teleport($this->getBaseLocation());
        $this->winners[] = $player;

        $validate = 0;

        foreach ($this->players as $key => $p) {
            if (!$p instanceof RPlayer) {
                unset($this->players[$key]);
                continue;
            }

            if (in_array($p, $this->winners, true)) {
                ++$validate;

                if (count($this->players) === $validate) {
                    $this->addPhase();
                }
            }
        }
    }

    public function endEvent(RPlayer $winner): void
    {
        $this->broadcastMessage("§6The event is over, the winner is §a{$winner->getName()}§6, he wins §a30 elos§6, thanks for your participation.");
        $winner->addElo(30);
        $level = $this->getLevel();
        $this->plugin->getServer()->getAsyncPool()->submitTask(new DeleteWorld($this->plugin->getServer()->getDataPath() . "worlds/" . $level->getFolderName(), $level));
    }
}