<?php

namespace Nathan45\Valea\Duels;

use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Listener\PracticeEvents\DuelCreateEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Tasks\Async\CreateWorld;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Inventories;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class Duel
{
    const STATUS_UNKNOWN = 0;
    const STATUS_PENDING = 1;
    const STATUS_PREPARATION = 2;
    const STATUS_PROGRESS = 3;
    const STATUS_FINISHED = 4;

    private int $status = 0;
    private array $team1 = [];
    private array $team2 = [];
    private int $unique_id;
    private ?World $level = null;
    private Cache $cache;
    private Loader $plugin;
    private ?Vector3 $tp1 = null;
    private ?Vector3 $tp2 = null;
    private array $time = [];
    private int $round = 1;
    private array $wonRounds = [];

    public function __construct(
        public RPlayer $player1,
        private string $mode,
        private bool $ranked = false,
        public int $players = 2,
        private ?RPlayer $player2 = null,
        private ?RPlayer $player3 = null,
        private ?RPlayer $player4 = null,
        private ?RPlayer $player5 = null,
        private ?RPlayer $player6 = null
    ) {
        $this->time[$this->player1->getName()] = time();
        $this->cache = Cache::getInstance();
        $this->plugin = Loader::getInstance();
        $this->setUniqueId($this->cache->duelCount);
        ++$this->cache->duelCount;
        $this->status = self::STATUS_PENDING;
    }

    public function getStatus(): int
    {
        return $this->status ?? self::STATUS_UNKNOWN;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getTp1(): ?Vector3
    {
        return $this->tp1;
    }

    public function getTp2(): ?Vector3
    {
        return $this->tp2;
    }

    public function setTp1(Vector3 $tp): void
    {
        $this->tp1 = $tp;
    }

    public function setTp2(Vector3 $tp): void
    {
        $this->tp2 = $tp;
    }

    public function addInQueue(RPlayer $player): void
    {
        $this->{"player" . count($this->getPlayers()) + 1} = $player;
        $this->time[$player->getName()] = time();
        if (count($this->getPlayers()) === $this->players) {
            $this->init();
        }
    }

    public function getPlayers(): array
    {
        $array = [];
        foreach ([$this->player1, $this->player2, $this->player3, $this->player4, $this->player5, $this->player6] as $p) {
            if (!is_null($p)) $array[] = $p;
        }
        return $array;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function addRound(): void
    {
        ++$this->round;
    }

    public function setCache(Cache $cache): void
    {
        $this->cache = $cache;
    }

    public function init(): void
    {
        switch (count($this->getPlayers())) {
            case 4:
                $this->addTeam1($this->getPlayers()[0]);
                $this->addTeam1($this->getPlayers()[1]);
                $this->addTeam2($this->getPlayers()[2]);
                $this->addTeam2($this->getPlayers()[3]);
                break;

            case 6:
                $this->addTeam1($this->getPlayers()[0]);
                $this->addTeam1($this->getPlayers()[1]);
                $this->addTeam1($this->getPlayers()[2]);
                $this->addTeam2($this->getPlayers()[3]);
                $this->addTeam2($this->getPlayers()[4]);
                $this->addTeam2($this->getPlayers()[5]);
                break;

            case 2:
            default:
                $this->addTeam1($this->getPlayers()[0]);
                $this->addTeam2($this->getPlayers()[1]);
                break;
        }

        $worlds = match (strtolower($this->getMode())) {
            "sumo"     => [IUtils::SUMO_DUEL_WORLD_NAME     => [new Vector3(256, 77, 247), new Vector3(256, 77, 265)]],
            "build"    => [IUtils::BUILD_DUEL_WORLD_NAME    => [new Vector3(256, 70, 277), new Vector3(254, 70, 233)]],
            "final"    => [IUtils::FINAL_DUEL_WORLD_NAME    => [new Vector3(256, 73, 231), new Vector3(256, 73, 278)]],
            "cave"     => [IUtils::CAVE_DUEL_WORLD_NAME     => [new Vector3(0, 90, 0),     new Vector3(50, 90, 0)]],
            "spleef"   => [IUtils::SPLEEF_DUEL_WORLD_NAME   => [new Vector3(0, 90, 0),     new Vector3(50, 90, 0)]],
            "bridge"   => [IUtils::BRIDGE_DUEL_WORLD_NAME   => [new Vector3(0, 90, 0),     new Vector3(50, 90, 0)]],
            "boxing"   => [IUtils::BOXING_DUEL_WORLD_NAME   => [new Vector3(0, 90, 0),     new Vector3(50, 90, 0)]],
            "fist"     => [IUtils::FIST_DUEL_WORLD_NAME     => [new Vector3(280, 67, 256), new Vector3(231, 67, 256)]],
            "nodebuff" => [IUtils::NODEBUFF_DUEL_WORLD_NAME => [new Vector3(256, 73, 231), new Vector3(258, 71, 278)]],
            "gapple"   => [IUtils::GAPPLE_DUEL_WORLD_NAME   => [new Vector3(254, 72, 277), new Vector3(254, 70, 233)]],
            default    => [IUtils::NODEBUFF_DUEL_WORLD_NAME => [new Vector3(254, 75, 231), new Vector3(256, 73, 278)]],
        };

        $world = array_rand($worlds);
        $this->setTp1($worlds[$world][0]);
        $this->setTp2($worlds[$world][1]);

        $this->plugin->getServer()->getWorldManager()->loadWorld($world);

        $event = new DuelCreateEvent($this);
        $event->call();
        $this->status = self::STATUS_PREPARATION;

        if (!$event->isCancelled()) {
            $this->plugin->getServer()->getAsyncPool()->submitTask(new CreateWorld(
                true,
                $this->getMode() . $this->getUniqueId(),
                $this->plugin->getServer()->getDataPath(),
                $world,
                $this
            ));
        }
    }

    public function setUniqueId(int $id): void
    {
        $this->unique_id = $id;
    }

    public function getUniqueId(): int
    {
        return $this->unique_id;
    }

    public function getLevel(): ?World
    {
        if (!$this->level instanceof World) return null;
        $this->plugin->getServer()->getWorldManager()->loadWorld($this->level->getFolderName());
        return $this->level;
    }

    public function broadcastMessage(string $message): void
    {
        foreach ($this->getPlayers() as $player) {
            if ($player instanceof RPlayer) $player->sendMessage($message);
        }
    }

    public function setLevel(World $level): void
    {
        $this->level = $level;
    }

    public function prepare(string $level_name): void
    {
        $worldManager = $this->plugin->getServer()->getWorldManager();
        $worldManager->loadWorld($level_name);
        $world = $worldManager->getWorldByName($level_name);
        $this->setLevel($world);

        $world->loadChunk((int) $this->getTp1()->getX() >> 4, (int) $this->getTp1()->getZ() >> 4);
        $world->loadChunk((int) $this->getTp2()->getX() >> 4, (int) $this->getTp2()->getZ() >> 4);

        foreach ($this->getPlayers() as $p) {
            if ($p instanceof Bot) {
                $p->setNameTagAlwaysVisible(true);
                $p->spawnToAll();
                $p->setInventory();
                $p->setCanSaveWithChunk(false);
                $p->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 99999 * 20, 0, false));
                $p->getArmorInventory()->setContents((new Inventories())->getArmorInventory(Inventories::INVENTORY_NODEBUFF));
                continue;
            }

            $this->wonRounds[$p->getName()] = 0;
            $p->setDuel($this);
            $p->setFreeze(10);
            $p->setGamemode(\pocketmine\player\GameMode::SURVIVAL);
            $p->reKit($this->mode);

            if ($this->getTeamFor($p) === 1) {
                $p->teleport(new Position($this->getTp1()->x, $this->getTp1()->y, $this->getTp1()->z, $this->getLevel()));
            } else {
                $p->teleport(new Position($this->getTp2()->x, $this->getTp2()->y, $this->getTp2()->z, $this->getLevel()));
            }
        }

        $this->status = self::STATUS_PROGRESS;
    }

    public function getId(): int
    {
        return match (strtolower($this->mode)) {
            "gapple"        => Cache::GAPPLE,
            "nodebuff"      => Cache::NODEBUFF,
            "sumo"          => Cache::SUMO,
            "fist"          => Cache::FIST,
            "build", "bu"   => Cache::BU,
            "final"         => Cache::FINAL,
            "cave"          => Cache::CAVE,
            "spleef"        => Cache::SPLEEF,
            "bridge"        => Cache::BRIDGE,
            "boxing"        => Cache::BOXING,
            default         => 0,
        };
    }

    public function isRanked(): bool
    {
        return $this->ranked;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getTeam1(): array
    {
        return $this->team1;
    }

    public function getTeam2(): array
    {
        return $this->team2;
    }

    public function removePlayer(RPlayer $player): void
    {
        if ($player === $this->player1) {
            $this->delete();
            return;
        }

        $bool = false;
        for ($int = 1; $int <= $this->players; $int++) {
            if ($bool && $this->{"player" . $int} !== null) {
                $i = $int - 1;
                $this->{"player" . $i} = $this->{"player" . $int};
                $this->{"player" . $int} = null;
            }
            if ($this->{"player" . $int} === $player) {
                $this->{"player" . $int} = null;
                $bool = true;
            }
        }
    }

    public function getTeamFor(RPlayer $player): int
    {
        if (in_array($player, $this->team1, true)) return 1;
        return 2;
    }

    public function addTeam1(RPlayer $player): void
    {
        $this->team1[] = $player;
    }

    public function addTeam2(RPlayer $player): void
    {
        $this->team2[] = $player;
    }

    public function getWaitingTimeFor(RPlayer $player): int
    {
        if (!isset($this->time[$player->getName()])) return 0;
        return time() - $this->time[$player->getName()];
    }

    public function delete(): void
    {
        foreach (Cache::getInstance()->getDuels() as $key => $duel) {
            if ($duel instanceof Duel && $duel->unique_id === $this->unique_id) {
                unset(Cache::getInstance()->duels[$key]);
            }
        }
    }

    public function getWonRoundsBy(string $player): int
    {
        return $this->wonRounds[$player];
    }

    public function getWonRounds(): array
    {
        return $this->wonRounds;
    }

    public function addWonRoundFor(string $player): void
    {
        ++$this->wonRounds[$player];
    }
}