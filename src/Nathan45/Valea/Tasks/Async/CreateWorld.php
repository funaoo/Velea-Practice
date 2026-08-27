<?php

namespace Nathan45\Valea\Tasks\Async;

use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Listener\PracticeEvents\BotDuelStartEvent;
use Nathan45\Valea\Utils\Inventories;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\Position;

class CreateWorld extends AsyncTask
{
    private string $newName;

    public function __construct(private bool $is_duel, string $newWorldName, private string $datapath, private string $levelName, ?Duel $duel = null, array $infos = [])
    {
        $this->storeLocal("duel", $duel);
        $this->storeLocal("info", $infos);
        $this->newName = $newWorldName;
    }

    public function onRun(): void
    {
        $name = $this->newName;
        @mkdir($this->datapath . "/worlds/$name/");
        @mkdir($this->datapath . "/worlds/$name/region/");
        copy($this->datapath . "/worlds/" . $this->levelName . "/level.dat", $this->datapath . "/worlds/$name/level.dat");
        $levelPath = $this->datapath . "/worlds/$name/level.dat";

        $nbtSerializer = new BigEndianNbtSerializer();
        $treeRoot = $nbtSerializer->readCompressed(file_get_contents($levelPath));
        $rootTag = $treeRoot->getTag();
        $data = $rootTag->getCompoundTag("Data");
        $data->setString("LevelName", $name);

        file_put_contents($levelPath, $nbtSerializer->writeCompressed($treeRoot));
        self::copy_directory($this->datapath . "/worlds/" . $this->levelName . "/region/", $this->datapath . "/worlds/$name/region/");
        $this->setResult($this->newName);
    }

    public function onCompletion(): void
    {
        $server = Server::getInstance();

        if ($this->is_duel) {
            $duel = $this->fetchLocal("duel");
            if ($duel instanceof Duel) $duel->prepare($this->getResult());
            return;
        }

        $array = $this->fetchLocal("info");
        $levelName = $this->getResult();
        $worldManager = $server->getWorldManager();
        $worldManager->loadWorld($levelName);
        $world = $worldManager->getWorldByName($levelName);

        $ev = new BotDuelStartEvent($array["player"], $array["bot"]);
        $ev->call();

        if ($ev->isCancelled()) {
            $server->getAsyncPool()->submitTask(new DeleteWorld($server->getDataPath() . "worlds/" . $levelName, $world));
            return;
        }

        $world->loadChunk((int) $array["tp1"]->getX() >> 4, (int) $array["tp1"]->getZ() >> 4);
        $world->loadChunk((int) $array["tp2"]->getX() >> 4, (int) $array["tp2"]->getZ() >> 4);

        foreach ([$array["player"], $array["bot"]] as $p) {
            if ($p instanceof Bot) {
                $p->setNameTagAlwaysVisible(true);
                $p->spawnToAll();
                $p->setInventory();
                $p->setCanSaveWithChunk(false);
                if (strtolower($array["mode"]) === "nodebuff") {
                    $p->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 99999 * 20, 0, false));
                    $p->getArmorInventory()->setContents((new Inventories())->getArmorInventory(Inventories::INVENTORY_NODEBUFF));
                }
                $p->teleport(new Position($array["tp2"]->x, $array["tp2"]->y, $array["tp2"]->z, $world));
                continue;
            }
            $p->setFreeze(10);
            $p->setGamemode(\pocketmine\player\GameMode::SURVIVAL);
            $p->reKit($array["mode"]);
            $p->removeQueue();
            $p->teleport(new Position($array["tp1"]->x, $array["tp1"]->y, $array["tp1"]->z, $world));
        }
    }

    public static function copy_directory(string $src, string $dst): void
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($src . '/' . $file)) {
                    self::copy_directory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}