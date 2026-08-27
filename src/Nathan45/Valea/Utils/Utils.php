<?php

namespace Nathan45\Valea\Utils;

use Nathan45\Valea\Database\SQLiteDatabase;
use Nathan45\Valea\Discord\Embed;
use Nathan45\Valea\Discord\Message;
use Nathan45\Valea\Discord\Webhook;
use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Entities\FloatingTextEntity;
use Nathan45\Valea\Listener\PracticeEvents\DuelCreateEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Tasks\Async\CreateWorld;
use Nathan45\Valea\Utils\Interfaces\ICache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as T;
use pocketmine\world\Position;

class Utils implements IUtils
{
    private ?Loader $plugin;
    private ?Cache $cache;
    public int $duels = 0;

    public function __construct()
    {
        $this->cache = Cache::getInstance();
        $this->plugin = Loader::getInstance();
    }

    public function getPlugin(): Loader
    {
        return $this->plugin;
    }

    public function accountExist(string $player): bool
    {
        return isset($this->cache->players[$player]);
    }

    public function getAllRanks(): array
    {
        return [
            "Player",
            "§6Valea+",
            "§dYoutube",
            "§aHelper",
            "§2TMod",
            "§3Mod",
            "§1SrMod",
            "§cAdmin",
            "§dDeveloper",
            "§4Manager",
            "§4Owner",
            "Builder",
        ];
    }

    public function setRank(string $target, int $rank): void
    {
        $t = $this->plugin->getServer()->getPlayerByPrefix($target);
        if (!isset($this->cache->players[$target])) return;
        $this->cache->players[$target][Cache::RANK] = $rank;
        if ($t instanceof RPlayer) {
            $t->setNameTag($t->getRank()->toString() . $t->getName());
        }
        $msg = new Message();
        $embed = new Embed();
        $embed->setTitle("Valea Rank");
        $embed->setColor(IUtils::RED);
        $embed->setFooter(date('l jS \of F Y h:i:s A'));
        $embed->setDescription($target . " received the rank " . ((new Rank($rank, null))->toString()));
        $msg->addEmbed($embed);
        (new Webhook(IUtils::RANK_WEBHOOK))->send($msg);
        SQLiteDatabase::getInstance()->update("valea", ["rank" => $rank], ["player" => $target]);
    }

    public function getRank(string $player, RPlayer|null $class): Rank
    {
        if (!isset($this->cache->players[$player])) return new Rank(Rank::RANK_DEFAULT, null);
        return new Rank($this->cache->players[$player][Cache::RANK], $class);
    }

    public function addBan(string $target, RPlayer $staff, int $time = 0, string $reason = "no reason"): void
    {
        if ($time === 0) $time = -time();
        SQLiteDatabase::getInstance()->insert("ban", [
            "player" => $target,
            "by_name" => $staff->getName(),
            "reason" => $reason,
            "time_sec" => $time + time(),
        ]);
        $this->cache->ban[$target] = [$staff->getName(), $time + time(), $reason];
        $p = $this->plugin->getServer()->getPlayerByPrefix($target);
        if ($p !== null) $p->kick("§cYou have just been banned from the server during " . $time / 86400 . " days for the reason : " . $reason);
        $duration = ($time === 0) ? "permanent" : $time / 86400 . " days";
        $msg = new Message();
        $embed = new Embed();
        $embed->setTitle("Valea Ban");
        $embed->setColor(IUtils::RED);
        $embed->setFooter(date('l jS \of F Y h:i:s A'));
        $embed->setDescription("User : " . $target . "\nModerator : " . $staff->getName() . "\nDuration : " . $duration . "\nReason : " . $reason);
        $msg->addEmbed($embed);
        (new Webhook(IUtils::BAN_WEBHOOK))->send($msg);
    }

    public function unban(string $target, ?RPlayer $staff = null): void
    {
        if (!isset($this->cache->ban[$target])) {
            $staff?->sendMessage(IUtils::ERROR . "§6{$target} §cis not banned !");
            return;
        }
        SQLiteDatabase::getInstance()->delete("ban", ["player" => $target]);
        unset($this->cache->ban[$target]);
        $msg = new Message();
        $embed = new Embed();
        $embed->setTitle("Valea Unban");
        $embed->setColor(IUtils::GREEN);
        $embed->setFooter(date('l jS \of F Y h:i:s A'));
        $embed->setDescription("User : $target, Moderator : " . (($staff === null) ? "console" : $staff->getName()));
        $msg->addEmbed($embed);
        (new Webhook(IUtils::UNBAN_WEBHOOK))->send($msg);
        $staff?->sendMessage(IUtils::PREFIX . "§6{$target} §ais now unbanned !");
    }

    public function stripColors(string $string): string
    {
        foreach ([T::BLACK, T::DARK_BLUE, T::DARK_GREEN, T::DARK_AQUA, T::DARK_RED, T::DARK_PURPLE, T::GOLD, T::GRAY, T::DARK_GRAY, T::BLUE, T::GREEN, T::AQUA, T::RED, T::LIGHT_PURPLE, T::YELLOW, T::WHITE, T::OBFUSCATED, T::BOLD, T::STRIKETHROUGH, T::UNDERLINE, T::ITALIC, T::RESET] as $color) {
            $string = str_replace($color, '', $string);
        }
        return $string;
    }

    public function containsVulgarities(string $string): bool
    {
        foreach (self::BANNED_WORDS as $word) {
            if (str_contains($word, strtolower($string))) return true;
        }
        return false;
    }

    public function isLink(string $string): bool
    {
        foreach (["www.", "https://"] as $contains) {
            if (str_contains($string, $contains)) return true;
        }
        return false;
    }

    public function spawnFloatingText(Position $pos, string $text): string
    {
        $world = $pos->getWorld();
        $location = new \pocketmine\entity\Location(
            $pos->getFloorX(),
            $pos->getFloorY(),
            $pos->getFloorZ(),
            $world,
            0.0,
            0.0
        );
        $floatingText = new FloatingTextEntity($location, new \pocketmine\entity\Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom", "{}"));
        $floatingText->initFloatingText();
        $floatingText->spawnToAll();
        $floatingText->setNameTag($text);
        return $floatingText->getFloatingTextId();
    }

    public function updateFloatingTextById(string $text, array|string $id): void
    {
        if (!is_array($id)) $id = [$id];

        foreach ($this->plugin->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof FloatingTextEntity) {
                    if (in_array($entity->getFloatingTextId(), $id)) {
                        $entity->setNameTag($text);
                    }
                }
            }
        }
    }

    public function startBotDuel(RPlayer $player, Bot $bot, string $mode): void
    {
        if (str_contains("nodebuff", strtolower($mode))) {
            $level = self::NODEBUFF_DUEL_WORLD_NAME;
            $tp1 = new Vector3(0, 90, 0);
            $tp2 = new Vector3(50, 90, 0);
        } else {
            $level = IUtils::SUMO_DUEL_WORLD_NAME;
            $tp1 = new Vector3(0, 90, 0);
            $tp2 = new Vector3(10, 90, 0);
        }
        $this->plugin->getServer()->getAsyncPool()->submitTask(new CreateWorld(false, 'Bot' . $this->cache->duelCount, $this->plugin->getServer()->getDataPath(), $level, null, ["player" => $player, "bot" => $bot, "mode" => $mode, "tp1" => $tp1, "tp2" => $tp2]));
        ++$this->cache->duelCount;
    }

    public function addInQueue(RPlayer $player, bool $ranked, int $players, string $data): void
    {
        $duel = $this->cache->getRealDuel($ranked, $players, $data);
        if (!is_null($duel) && in_array($player, $this->cache->getRealDuel($ranked, $players, $data)->getPlayers(), true)) {
            if ($duel->player1 === $player) $duel->delete();
            else $duel->removePlayer($player);
            $player->sendMessage(IMessages::LEAVE_QUEUE);
            return;
        }
        $player->removeQueue();
        if ($this->cache->getDuel($ranked, $players, $data) === 0) {
            $duel = new Duel($player, $data, $ranked, $players);
            $this->cache->duels[$duel->getUniqueId()] = $duel;
        } else {
            $this->cache->getRealDuel($ranked, $players, $data)->addInQueue($player);
        }
        $player->sendMessage(IMessages::JOINED_QUEUE);
    }

    public function fineBoxing(RPlayer $damager, RPlayer $victim): void
    {
        $damager->setHit();
        $victim->setHit();
        $victim->kill();
        $victim->setLastDamageCause(new EntityDamageEvent($victim, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 100));
        (new PlayerDeathEvent($victim, null, null, null, [], null))->call();
    }

    public function joinFfa(RPlayer $player, string $mode): void
    {
        $pos = $this->getPositionForFfa($mode);
        if ($pos === null) return;
        $player->teleport($pos);
        $player->reKit($mode);
        $this->cache->addInFfa($this->getIdForFfa($mode), $player);
    }

    public function getIdForFfa(string $mode): int
    {
        return match (strtolower($mode)) {
            "rush"                     => Cache::RUSH,
            "soup"                     => Cache::SOUP,
            "boxing"                   => Cache::BOXING,
            "gapple"                   => Cache::GAPPLE,
            "nodebuff"                 => Cache::NODEBUFF,
            "sumo"                     => Cache::SUMO,
            "fist"                     => Cache::FIST,
            "bu", "builduhc", "build"  => Cache::BU,
            "combo"                    => Cache::COMBO,
            default                    => 0,
        };
    }

    public function getPositionForFfa(string $ffa): ?Position
    {
        switch (strtolower($ffa)) {
            case "rush":
                $worldName = self::RUSH_FFA_WORLD_NAME;
                $vector = [
                    new Vector3(-65, 10, 31),
                    new Vector3(-65, 10, 0),
                    new Vector3(-65, 10, -31),
                    new Vector3(-31, 10, -65),
                    new Vector3(0, 10, -65),
                    new Vector3(31, 10, -65),
                    new Vector3(65, 10, -31),
                    new Vector3(65, 10, 0),
                    new Vector3(65, 10, 31),
                    new Vector3(31, 10, 65),
                    new Vector3(0, 10, 65),
                    new Vector3(-31, 10, 65),
                ];
                break;

            case "soup":
                $worldName = self::SOUP_FFA_WORLD_NAME;
                $vector = new Vector3(self::SOUP_FFA_X, self::SOUP_FFA_Y, self::SOUP_FFA_Z);
                break;

            case "boxing":
                $worldName = self::BOXING_FFA_WORLD_NAME;
                $vector = new Vector3(self::BOXING_FFA_X, self::BOXING_FFA_Y, self::BOXING_FFA_Z);
                break;

            case "gapple":
                $worldName = self::GAPPLE_FFA_WORLD_NAME;
                $vector = new Vector3(self::GAPPLE_FFA_X, self::GAPPLE_FFA_Y, self::GAPPLE_FFA_Z);
                break;

            case "nodebuff":
                $worldName = self::NODEBUFF_FFA_WORLD_NAME;
                $vector = new Vector3(self::NODEBUFF_FFA_X, self::NODEBUFF_FFA_Y, self::NODEBUFF_FFA_Z);
                break;

            case "sumo":
                $worldName = self::SUMO_FFA_WORLD_NAME;
                $vector = new Vector3(self::SUMO_FFA_X, self::SUMO_FFA_Y, self::SUMO_FFA_Z);
                break;

            case "fist":
                $worldName = self::FIST_FFA_WORLD_NAME;
                $vector = new Vector3(self::FIST_FFA_X, self::FIST_FFA_Y, self::FIST_FFA_Z);
                break;

            case "combo":
                $worldName = self::COMBO_FFA_WORLD_NAME;
                $vector = new Vector3(self::COMBO_FFA_X, self::COMBO_FFA_Y, self::COMBO_FFA_Z);
                break;

            default:
                return null;
        }

        $worldManager = $this->plugin->getServer()->getWorldManager();
        $worldManager->loadWorld($worldName);
        $world = $worldManager->getWorldByName($worldName);

        if ($world === null) return null;
        if (is_array($vector)) $vector = $vector[array_rand($vector)];
        if (!$vector instanceof Vector3) return null;

        return new Position($vector->x, $vector->y, $vector->z, $world);
    }

    public function getStatMessage(int $type): string
    {
        $array = [];
        $message = "";
        foreach ($this->cache->players as $player => $arr) {
            $array[$player] = $arr[$type];
        }
        $array = $this->filterArray($array);
        if (count($array) < 10) {
            for ($i = count($array); $i <= 10; $i++) {
                $array["/"] = 0;
            }
        }
        foreach ($array as $p => $record) {
            $message .= "§6{$p} §7=> §b{$record}\n";
        }
        return $message;
    }

    public function filterArray(array $array): array
    {
        $arr = [];
        for ($i = 0; $i <= 10; $i++) {
            $max = max($array);
            $arr[array_search($max, $array)] = $max;
            unset($array[array_search($max, $array)]);
        }
        return $arr;
    }
}
