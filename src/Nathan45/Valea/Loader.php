<?php

namespace Nathan45\Valea;

use Nathan45\Valea\Commands\AutoSprintCommand;
use Nathan45\Valea\Commands\InfoCommand;
use Nathan45\Valea\Commands\InventoryCommand;
use Nathan45\Valea\Commands\OnlineCommand;
use Nathan45\Valea\Commands\PingCommand;
use Nathan45\Valea\Commands\ProfileCommand;
use Nathan45\Valea\Commands\RankEditor;
use Nathan45\Valea\Commands\RekitCommand;
use Nathan45\Valea\Commands\ReportCommand;
use Nathan45\Valea\Commands\RulesCommand;
use Nathan45\Valea\Commands\SpawnCommand;
use Nathan45\Valea\Commands\Staff\BanCommand;
use Nathan45\Valea\Commands\Staff\BotCommand;
use Nathan45\Valea\Commands\Staff\Buildperms;
use Nathan45\Valea\Commands\Staff\ClearSkinCommand;
use Nathan45\Valea\Commands\Staff\EventCommand;
use Nathan45\Valea\Commands\Staff\FlyCommand;
use Nathan45\Valea\Commands\Staff\FreezeCommand;
use Nathan45\Valea\Commands\Staff\MeCommand;
use Nathan45\Valea\Commands\Staff\MuteCommand;
use Nathan45\Valea\Commands\Staff\RankCommand;
use Nathan45\Valea\Commands\Staff\UnBanCommand;
use Nathan45\Valea\Commands\Staff\VanishCommand;
use Nathan45\Valea\Commands\Staff\WhoCommand;
use Nathan45\Valea\Database\SQLiteDatabase;
use Nathan45\Valea\Entities\Bots\NoDeBuffBot;
use Nathan45\Valea\Entities\FloatingTextEntity;
use Nathan45\Valea\Entities\Hook;
use Nathan45\Valea\Entities\ValeaPotion;
use Nathan45\Valea\Items\FishingRod;
use Nathan45\Valea\Items\GoldenHead;
use Nathan45\Valea\Items\ValeaPearl;
use Nathan45\Valea\Listener\EntityListener;
use Nathan45\Valea\Listener\PlayerListener;
use Nathan45\Valea\Listener\PracticeListener;
use Nathan45\Valea\Tasks\Regular\BroadcastTask;
use Nathan45\Valea\Tasks\Regular\CpsTask;
use Nathan45\Valea\Tasks\Regular\PracticeTask;
use Nathan45\Valea\Tasks\Regular\ScoreboardTask;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Utils;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\item\PotionType;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Loader extends PluginBase
{
    private static self $instance;
    public array $floatingTexts = [];

    private static function deserializeInventoriesFromDB(string $raw): array
    {
        if (empty($raw)) return [];
        $decoded = base64_decode($raw);
        if ($decoded === false) return [];
        $data = json_decode($decoded, true);
        if (is_array($data)) {
            $serializer = new LittleEndianNbtSerializer();
            $inventories = [];
            foreach ($data as $slotKey => $inv) {
                $inventories[(int)$slotKey] = [];
                foreach ($inv as $index => $itemData) {
                    try {
                        $nbt = $serializer->read(base64_decode($itemData))->mustGetCompoundTag();
                        $inventories[(int)$slotKey][(int)$index] = Item::nbtDeserialize($nbt);
                    } catch (\Exception $e) {}
                }
            }
            return $inventories;
        }
        return [];
    }

    public function onEnable(): void
    {
        self::$instance = $this;
        new Cache();
        new SQLiteDatabase($this->getDataFolder());
        $msg = <<<TAG

 __      __     _      ______          
 \ \    / /\   | |    |  ____|   /\    
  \ \  / /  \  | |    | |__     /  \   
   \ \/ / /\ \ | |    |  __|   / /\ \  
    \  / ____ \| |____| |____ / ____ \ 
     \/_/    \_\______|______/_/    \_\
                              
TAG;
        $this->getLogger()->info($msg);
        $this->registerEntities();
        $this->loadListener();
        $this->initDatabase();
        $this->loadResources();
        $this->unloadCommands();
        $this->loadItems();
        $this->loadLevels();
        $this->launchTasks();
        $this->loadCommands();
        $this->initFloatingTexts();
        $this->clearEntities();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    private function loadCommands(): void
    {
        $this->getServer()->getCommandMap()->registerAll("valea", [
            new SpawnCommand($this),
            new AutoSprintCommand($this),
            new BanCommand($this),
            new UnBanCommand($this),
            new ProfileCommand($this),
            new FreezeCommand($this),
            new MuteCommand(),
            new WhoCommand(),
            new Buildperms($this),
            new VanishCommand(),
            new FlyCommand($this),
            new MeCommand($this),
            new RankCommand(),
            new RankEditor(),
            new InfoCommand(),
            new OnlineCommand($this),
            new PingCommand(),
            new ReportCommand($this),
            new BotCommand(),
            new InventoryCommand($this),
            new RulesCommand(),
            new RekitCommand($this),
            new ClearSkinCommand($this),
        ]);

    }

    public function addPermissions(Player $player, array $perms = []): void
    {
        foreach ($perms as $perm) {
            $attachment = $player->addAttachment($this);
            $attachment->setPermission($perm, true);
        }
    }

    private function unloadCommands(): void
    {
        $commands = $this->getServer()->getCommandMap();
        foreach (["ban", "unban", "me"] as $cmd) {
            $command = $commands->getCommand($cmd);
            if ($command !== null) {
                $command->setLabel("old_" . $command->getName());
                $commands->unregister($command);
            }
        }
    }

    private function loadItems(): void
    {
        $deserializer = GlobalItemDataHandlers::getDeserializer();
        $deserializer->map("minecraft:fishing_rod", fn(SavedItemData $d) => new FishingRod());
        $deserializer->map("minecraft:ender_pearl", fn(SavedItemData $d) => new ValeaPearl());
        $deserializer->map("minecraft:salmon", fn(SavedItemData $d) => new GoldenHead());
    }

    private function loadResources(): void
    {
        @mkdir($this->getDataFolder() . "capes");
        $array = (new FormsManager())->capes;
        $resources = [];
        foreach ($array as $cape) {
            $resources[] = "capes/{$cape}.png";
        }
        foreach ($resources as $filename) {
            $this->saveResource($filename);
        }
    }

    private function clearEntities(): void
    {
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                $entity->flagForDespawn();
            }
        }
    }

    public function initDatabase(): void
    {
        $db = SQLiteDatabase::getInstance();
        $rows = $db->all("SELECT * FROM `ban`");
        $ban = [];
        foreach ($rows as $row) {
            $ban[$row["player"]] = [
                $row["by_name"],
                (int) $row["time_sec"],
                $row["reason"],
            ];
        }

        $rows = $db->all("SELECT * FROM `valea`");
        $players = [];
        foreach ($rows as $row) {
            $players[$row["player"]] = [
                (int) $row["coins"],
                (int) $row["kills"],
                (int) $row["death"],
                (int) $row["rank"],
                (int) $row["elo"],
                $row["cps"],
                $row["ip"],
                $row["id"],
                unserialize(base64_decode($row["friends"] ?? base64_encode(serialize([])))),
                self::deserializeInventoriesFromDB($row["inventories"] ?? ""),
                $row["scoreboard"],
                $row["death_message"],
            ];
        }

        $cache = Cache::getInstance();
        $cache->setBan($ban);
        $cache->setPlayers($players);
    }

    public function onDisable(): void
    {
        SQLiteDatabase::getInstance()?->close();
    }

    private static function createDummySkin(): Skin
    {
        return new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom", "{}");
    }

    public function registerEntities(): void
    {
        $factory = EntityFactory::getInstance();

        $factory->register(FloatingTextEntity::class, function(\pocketmine\world\World $world, CompoundTag $nbt): FloatingTextEntity {
            return new FloatingTextEntity(EntityDataHelper::parseLocation($nbt, $world), self::createDummySkin(), $nbt);
        }, ["FloatingTextEntity"]);

        $factory->register(Hook::class, function(\pocketmine\world\World $world, CompoundTag $nbt): Hook {
            return new Hook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["FishingHook", "minecraft:fishinghook"]);

        $factory->register(NoDeBuffBot::class, function(\pocketmine\world\World $world, CompoundTag $nbt): NoDeBuffBot {
            return new NoDeBuffBot(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["NoDeBuffBot"]);

        $factory->register(ValeaPotion::class, function(\pocketmine\world\World $world, CompoundTag $nbt): ValeaPotion {
            $potionId = $nbt->getShort(\pocketmine\entity\projectile\SplashPotion::TAG_POTION_ID, 0);
            $potionType = PotionType::tryFrom($potionId) ?? PotionType::WATER;
            return new ValeaPotion(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
        }, ["ThrownPotion", "minecraft:potion", "thrownpotion"]);
    }

    private function loadListener(): void
    {
        foreach ([
                     new PlayerListener($this),
                     new PracticeListener($this),
                     new EntityListener($this),
                 ] as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }
    }

    private function launchTasks(): void
    {
        foreach ([
                     BroadcastTask::class,
                     ScoreboardTask::class,
                     CpsTask::class,
                 ] as $class) {
            $c = new $class();
            if ($c instanceof PracticeTask) {
                $this->getScheduler()->scheduleRepeatingTask($c, $c->getPeriod());
                $c->setStatus(PracticeTask::STATUS_RUNNING);
            }
        }
    }

    public function initFloatingTexts(): void
    {
        $worldManager = $this->getServer()->getWorldManager();
        $worldManager->loadWorld(IUtils::LOBBY_WORLD_NAME);
        $lobbyWorld = $worldManager->getWorldByName(IUtils::LOBBY_WORLD_NAME);

        foreach ([
                     "§3Valea §fNetwork\nRules of the server:\n" => new Position(-13, 84, 9, $lobbyWorld),
                 ] as $text => $pos) {
            $pos->getWorld()->loadChunk((int) $pos->x >> 4, (int) $pos->z >> 4);
            $this->floatingTexts[$text] = (new Utils())->spawnFloatingText($pos, $text);
        }
    }

    private function loadLevels(): void
    {
        $worldManager = $this->getServer()->getWorldManager();

        foreach (array_diff(scandir($this->getServer()->getDataPath() . "worlds"), ["..", "."]) as $levelName) {
            $worldManager->loadWorld($levelName, true);
        }

        foreach ($worldManager->getWorlds() as $world) {
            $world->setTime(0);
            $world->stopTime();
        }
    }

    public function getPropertyType($value): int
    {
        if (is_bool($value)) return 1;
        if (is_int($value)) return 2;
        return 0;
    }
}
