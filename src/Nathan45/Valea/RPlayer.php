<?php

namespace Nathan45\Valea;

use Nathan45\Valea\Commands\AutoSprintCommand;
use Nathan45\Valea\Database\SQLiteDatabase;
use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Entities\Hook;
use Nathan45\Valea\Events\Event;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\ICache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Inventories;
use Nathan45\Valea\Utils\Rank;
use Nathan45\Valea\Utils\Utils;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use pocketmine\entity\Skin;

class RPlayer extends Player implements ICache
{
    private Loader $plugin;
    private Cache $cache;
    private Utils $utils;

    public int $currentCPS = 0;
    public array $clicks = [];
    public array $cpsList = [];

    private array $clicksData = [];
    private bool $vanish = false;
    private bool $muted = false;
    private bool $formOpen = false;
    private int $inventoryId = 0;
    private ?Duel $duel = null;
    private ?Event $event = null;
    private bool $nick = false;
    private int $chatCooldown = 0;

    protected function initEntity(\pocketmine\nbt\tag\CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->plugin = Loader::getInstance();
        $this->cache = Cache::getInstance();
        $this->utils = new Utils();
    }

    public function knockBack(float $x, float $z, float $force = self::DEFAULT_KNOCKBACK_FORCE, ?float $verticalLimit = self::DEFAULT_KNOCKBACK_VERTICAL_LIMIT): void
    {
        $f = sqrt($x * $x + $z * $z);
        if ($f <= 0) return;

        if (mt_rand() / mt_getrandmax() > $this->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)->getValue()) {
            $f = 1 / $f;

            $kbx = IUtils::KB_X;
            $kby = IUtils::KB_Y;
            $kbz = IUtils::KB_Z;

            $motion = clone $this->getMotion();

            $motion->x /= 2;
            $motion->y /= 2;
            $motion->z /= 2;
            $motion->x += $x * $f * $kbx;
            $motion->y += $kby;
            $motion->z += $z * $f * $kbz;

            if ($motion->y > $kby) {
                $motion->y = $kby;
            }

            $this->setMotion($motion);
        }
    }

    public function setHealth(float $amount): void
    {
        parent::setHealth($amount);
        $this->getAttributeMap()->get(Attribute::HEALTH)->setValue(ceil($this->getHealth()), true);
        if (!isset($this->utils)) return;
        $hp = max(0, (int) ceil($amount));
        $this->setNameTag($this->getRank()->toString() . $this->getName());
    }

    public function resetItemCooldown(Item $item, ?int $ticks = null): void
    {
        if (!is_numeric($ticks)) $ticks = $item->getCooldownTicks();
        if ($ticks > 0) {
            $this->cache->pearls[$this->getName()] = time() + ($ticks / 20);
        }
        parent::resetItemCooldown($item, $ticks);
    }

    public function getInventories(): array
    {
        if (empty($this->cache->players[$this->getName()][self::INVENTORIES] ?? [])) {
            return (new Inventories())->getBaseInventories();
        }
        return $this->cache->players[$this->getName()][self::INVENTORIES];
    }

    public function setInventory(array $inventory): void
    {
        $this->cache->players[$this->getName()][self::INVENTORIES][$this->getInventoryId()] = $inventory;
        for ($i = 0; $i < 10; $i++) {
            if (!isset($this->cache->players[$this->getName()][self::INVENTORIES][$i])) {
                $this->cache->players[$this->getName()][self::INVENTORIES][$i] = (new Inventories())->getInventory($i);
            }
        }
        SQLiteDatabase::getInstance()->update("valea", ["inventories" => $this->serializeInventories($this->getInventories())], ["player" => $this->getName()]);
    }

    public function playSound(string $sound): void
    {
        $pos = $this->getPosition();
        $pk = PlaySoundPacket::create($sound, $pos->x, $pos->y, $pos->z, 100, 1);
        $this->getNetworkSession()->sendDataPacket($pk);
    }

    public function reKit(int|string $inventory): void
    {
        if (is_string($inventory)) {
            $inventory = match (strtolower($inventory)) {
                "nodebuff", "nodebuffffa", strtolower(IUtils::NODEBUFF_FFA_WORLD_NAME), strtolower(IUtils::NODEBUFF_DUEL_WORLD_NAME), strtolower(IUtils::NODEBUFF_EVENT_WORLD_NAME) => 1,
                "gapple", "gappleffa", strtolower(IUtils::GAPPLE_FFA_WORLD_NAME), strtolower(IUtils::GAPPLE_DUEL_WORLD_NAME) => 2,
                "sumo", "sumoffa", strtolower(IUtils::SUMO_FFA_WORLD_NAME), strtolower(IUtils::SUMO_DUEL_WORLD_NAME) => 3,
                "fist", "fistffa", strtolower(IUtils::FIST_FFA_WORLD_NAME), strtolower(IUtils::FIST_DUEL_WORLD_NAME) => 4,
                "rush", "rushffa", strtolower(IUtils::RUSH_FFA_WORLD_NAME) => 5,
                "soup", "soupffa", strtolower(IUtils::SOUP_FFA_WORLD_NAME) => 6,
                "boxing", "boxingffa", strtolower(IUtils::BOXING_DUEL_WORLD_NAME), strtolower(IUtils::BOXING_FFA_WORLD_NAME) => 7,
                "bu", "builduhc", "buffa", "builduhcffa", "build", strtolower(IUtils::BUILD_DUEL_WORLD_NAME) => 8,
                "combo", "comboffa", strtolower(IUtils::COMBO_FFA_WORLD_NAME) => 9,
                default => 0,
            };
        }

        if ($inventory === -1) {
            $this->getInventory()->setContents((new Inventories())->getInventory($inventory));
            $this->getArmorInventory()->clearAll();
        } else {
            $this->getInventory()->setContents($this->getInventories()[$inventory] ?? []);
            $this->getArmorInventory()->setContents((new Inventories())->getArmorInventory($inventory));
        }

        $this->setHealth($this->getMaxHealth());
        $this->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999 * 20, 0, false));
    }

    public function setCape(string $cape): void
    {
        $oldSkin = $this->getSkin();
        $capeData = $this->createCape($cape);
        $newSkin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
        $this->setSkin($newSkin);
        $this->sendSkin();
    }

    public function createCape(string $cape): string
    {
        $path = $this->plugin->getDataFolder() . "capes/{$cape}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        $l = (int) @getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $bytes;
    }

    public function setVanish(bool $vanish = true): void
    {
        $this->vanish = $vanish;
        if ($vanish) {
            $this->setInvisible(true);
            $this->getArmorInventory()->setContents([]);
            $this->reKit(Inventories::VANISH_INVENTORY);
            return;
        }
        $this->setInvisible(false);
        $this->plugin->getServer()->getCommandMap()->dispatch($this, "spawn");
    }

    public function getCps(): int
    {
        if (empty($this->clicksData)) return 0;
        $ct = microtime(true);
        return (int) round(count(array_filter($this->clicksData, static function (float $t) use ($ct): bool {
            return ($ct - $t) <= 1.0;
        })), 1);
    }

    public function addClick(): void
    {
        $currentTime = microtime(true);
        array_unshift($this->clicksData, $currentTime);
        $this->clicksData = array_filter($this->clicksData, function (float $last) use ($currentTime): bool {
            return $currentTime - $last <= 1;
        });
        if (count($this->clicksData) >= 100) array_pop($this->clicksData);
    }

    public function removeClickData(): void
    {
        $this->clicksData = [];
    }

    public function getFriends(): array
    {
        return $this->cache->players[$this->getName()][ICache::FRIENDS] ?? [];
    }

    public function sendFriendRequestTo(RPlayer $target): void
    {
        $target->sendMessage(str_replace("{player}", $this->getName(), IMessages::FRIEND_REQUEST));
        if (!isset($this->cache->requests[$this->getName()])) $this->cache->requests[$this->getName()] = [];
        $this->cache->requests[$this->getName()][] = $target;
    }

    public function getOnlineFriends(): array
    {
        $array = [];
        foreach ($this->getFriends() as $friend) {
            $p = $this->plugin->getServer()->getPlayerExact($friend);
            if ($p instanceof RPlayer) $array[] = $p;
        }
        return $array;
    }

    public function addFriend(string $requestor): void
    {
        if (!isset($this->cache->players[$this->getName()][ICache::FRIENDS])) {
            $this->cache->players[$this->getName()][ICache::FRIENDS] = [];
        }
        $this->cache->players[$this->getName()][ICache::FRIENDS][] = $requestor;
        SQLiteDatabase::getInstance()->update("valea", ["friends" => base64_encode(serialize($this->cache->players[$this->getName()][ICache::FRIENDS]))], ["player" => $this->getName()]);
        $this->sendMessage(IUtils::PREFIX . "§aYou are now friends with {$requestor}");
        $player = $this->plugin->getServer()->getPlayerExact($requestor);
        if ($player instanceof RPlayer) $player->sendMessage(IUtils::PREFIX . "§aYou are now friends with {$this->getName()}");
    }

    public function removeRequest(string $requestor): void
    {
        $this->sendMessage(IUtils::PREFIX . "§aYou did refuse {$requestor}'s friend request.");
        unset($this->cache->requests[$requestor][array_search($this, $this->cache->requests[$requestor], true)]);
    }

    public function getFriendsRequests(): array
    {
        $arr = [];
        foreach ($this->cache->requests as $requestor => $array) {
            if (in_array($this, $array, true)) $arr[] = $requestor;
        }
        return $arr;
    }

    public function removeQueue(): void
    {
        foreach ($this->cache->getDuels() as $duel) {
            if ($duel instanceof Duel && in_array($this, $duel->getPlayers(), true)) {
                $duel->removePlayer($this);
            }
        }
    }

    public function setDuel(?Duel $duel): void
    {
        $this->duel = $duel;
    }

    public function getDuel(): ?Duel
    {
        return $this->duel;
    }

    public function isInQueue(): false|Duel
    {
        if (empty($this->cache->getDuels())) return false;
        foreach ($this->cache->duels as $duel) {
            if ($duel instanceof Duel && in_array($this, $duel->getPlayers(), true)) return $duel;
        }
        return false;
    }

    public function setInCombat(RPlayer $fighter, int $time = 15): void
    {
        $this->cache->combats[$this->getName()][0] = time() + $time;
        $this->cache->combats[$this->getName()][1] = $fighter->getName();
        $this->getScoreboard();
        if (isset($this->cache->scoreboards[$this->getName()])) {
            $this->cache->scoreboards[$this->getName()]->setTarget($fighter);
        }
        if (!$this->isOnCombat()) $this->sendMessage(IUtils::PREFIX . "§aYou are now in combat with " . $fighter->getName());
        if (!$fighter->isOnCombat()) $fighter->sendMessage(IUtils::PREFIX . "§aYou are now in combat with " . $this->getName());
    }

    public function setFreeze(int $time, RPlayer|null $staff = null): void
    {
        $this->cache->freeze[$this->getName()] = time() + $time;
        $this->setNoClientPredictions(true);
        if ($staff !== null) $staff->sendMessage(IUtils::PREFIX . "§6{$this->getName()} §ais now freeze for §6$time §aseconds !");
    }

    private function serializeInventories(array $inventories): string
    {
        $serializer = new LittleEndianNbtSerializer();
        $data = [];
        foreach ($inventories as $slotKey => $inv) {
            $slotData = [];
            foreach ($inv as $index => $item) {
                if ($item instanceof Item && !$item->isNull()) {
                    $slotData[(string)$index] = base64_encode($serializer->write(new TreeRoot($item->nbtSerialize())));
                }
            }
            $data[(string)$slotKey] = $slotData;
        }
        return base64_encode(json_encode($data));
    }

    private function deserializeInventories(string $encoded): array
    {
        $serializer = new LittleEndianNbtSerializer();
        $data = json_decode(base64_decode($encoded), true);
        if (!is_array($data)) return [];
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

    public function createAccount(): void
    {
        $name = $this->getName();
        $address = $this->getNetworkSession()->getIp();
        $uuid = $this->getXuid();
        $friends = base64_encode(serialize([]));
        $inv = $this->serializeInventories($this->getInventories());
        if (!isset($this->cache->players[$this->getName()])) {
            SQLiteDatabase::getInstance()->insert("valea", [
                "player" => $name,
                "coins" => 0,
                "kills" => 0,
                "death" => 0,
                "rank" => 0,
                "elo" => 0,
                "cps" => "true",
                "ip" => $address,
                "id" => $uuid,
                "friends" => $friends,
                "inventories" => $inv,
                "scoreboard" => "true",
                "death_message" => "true",
            ], true);
            $this->cache->players[$name] = [0, 0, 0, 0, 0, 'true', $address, $uuid, [], $this->getInventories(), "true", "true"];
        } elseif (!isset($this->cache->players[$this->getName()][ICache::DEATH_MESSAGE])) {
            $this->setDeathMessage(true);
        }
    }

    public function setSpectate(bool $bool = true): void
    {
        if ($bool) {
            $this->setGamemode(\pocketmine\player\GameMode::SPECTATOR);
            $this->sendTitle("§cYou are dead!");
            return;
        }
        $this->setGamemode(\pocketmine\player\GameMode::SURVIVAL);
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    public function getCoins(): int
    {
        return $this->cache->players[$this->getName()][self::COINS] ?? 0;
    }

    public function setCoins(int $coins): void
    {
        $this->cache->players[$this->getName()][self::COINS] = $coins;
        SQLiteDatabase::getInstance()->update("valea", ["coins" => $coins], ["player" => $this->getName()]);
    }

    public function addElo(int $elo): void
    {
        $total = $this->getElo() + $elo;
        $this->cache->players[$this->getName()][ICache::ELO] = $total;
        SQLiteDatabase::getInstance()->update("valea", ["elo" => $total], ["player" => $this->getName()]);
    }

    public function getElo(): int
    {
        return $this->cache->players[$this->getName()][ICache::ELO] ?? 0;
    }

    public function addKill(): void
    {
        if (!is_numeric($this->cache->players[$this->getName()][self::KILLS] ?? null)) {
            $this->cache->players[$this->getName()][self::KILLS] = 1;
            SQLiteDatabase::getInstance()->update("valea", ["kills" => 1], ["player" => $this->getName()]);
            return;
        }
        ++$this->cache->players[$this->getName()][self::KILLS];
        $kills = $this->cache->players[$this->getName()][self::KILLS];
        SQLiteDatabase::getInstance()->update("valea", ["kills" => $kills], ["player" => $this->getName()]);
        $this->setCoins($this->getCoins() + 10);
    }

    public function getScoreboard(): void
    {
        if (!$this->getAllowedScoreboard()) return;
        $sco = $this->cache->scoreboards[$this->getName()] ?? new Scoreboard($this, Scoreboard::SCOREBOARD_LOBBY);
        $this->cache->scoreboards[$this->getName()] = $sco;
    }

    public function getDeath(): int
    {
        return $this->cache->players[$this->getName()][self::DEATH] ?? 0;
    }

    public function addDeath(): void
    {
        $death = ++$this->cache->players[$this->getName()][self::DEATH];
        SQLiteDatabase::getInstance()->update("valea", ["death" => $death], ["player" => $this->getName()]);
    }

    public function getRank(): Rank
    {
        return $this->utils->getRank($this->getName(), $this);
    }

    public function setRank(int $rank): void
    {
        $this->utils->setRank($this->getName(), $rank);
    }

    public function setElo(int $elo): void
    {
        $this->cache->players[$this->getName()][self::ELO] = $elo;
        SQLiteDatabase::getInstance()->update("valea", ["elo" => $elo], ["player" => $this->getName()]);
    }

    public function getKills(): int
    {
        return $this->cache->players[$this->getName()][self::KILLS] ?? 0;
    }

    public function getFishingHook(): null|Hook
    {
        return $this->cache->fishing[$this->getName()] ?? null;
    }

    public function setFishingHook(null|Hook $fish): void
    {
        $this->cache->fishing[$this->getName()] = $fish;
    }

    public function getCpsCounter(): string
    {
        return $this->cache->players[$this->getName()][self::CPS] ?? "true";
    }

    public function setCpsCounter(string $bool): void
    {
        $this->cache->players[$this->getName()][self::CPS] = $bool;
        SQLiteDatabase::getInstance()->update("valea", ["cps" => $bool], ["player" => $this->getName()]);
    }

    public function hasAccount(): bool
    {
        return isset($this->cache->players[$this->getName()]) && isset($this->cache->players[$this->getName()][ICache::DEATH_MESSAGE]);
    }

    public function setMuted(bool $mute = true): void
    {
        $this->muted = $mute;
    }

    public function isFormOpen(): bool
    {
        return $this->formOpen;
    }

    public function setFormOpen(bool $open): void
    {
        $this->formOpen = $open;
    }

    public function isMuted(): bool
    {
        return $this->muted;
    }

    public function isVanish(): bool
    {
        return $this->vanish;
    }

    public function isFreeze(): bool
    {
        $bool = isset($this->cache->freeze[$this->getName()]) && $this->cache->freeze[$this->getName()] > time();
        if (!$bool) $this->setNoClientPredictions(false);
        return $bool;
    }

    public function unFreeze(): void
    {
        unset($this->cache->freeze[$this->getName()]);
    }

    public function getFreezeTime(): ?int
    {
        if (!$this->isFreeze()) return null;
        return $this->cache->freeze[$this->getName()] - time();
    }

    public function isOnCombat(): bool
    {
        return isset($this->cache->combats[$this->getName()][0]) && $this->cache->combats[$this->getName()][0] > time();
    }

    public function getCombatTime(): int
    {
        if (!$this->isOnCombat()) return 0;
        return $this->cache->combats[$this->getName()][0] - time();
    }

    public function getPearlCooldown(): int|string
    {
        return ((!isset($this->cache->pearls[$this->getName()])) || $this->cache->pearls[$this->getName()] - time() <= 0)
            ? "§aAvailable"
            : $this->cache->pearls[$this->getName()] - time();
    }

    public function getFighter(): ?Player
    {
        if (!$this->isOnCombat()) return null;
        return $this->plugin->getServer()->getPlayerExact($this->cache->combats[$this->getName()][1]);
    }

    public function removeCombat(): void
    {
        unset($this->cache->combats[$this->getName()]);
        $this->getScoreboard();
    }

    public function getAutoSprint(): string
    {
        return AutoSprintCommand::isInSprintMode($this->getName()) ? "Enabled" : "Disabled";
    }

    public function isBanned(): bool
    {
        return isset($this->cache->ban[$this->getName()]) && ($this->cache->ban[$this->getName()][FormsManager::TIME] - time() > 0 || $this->cache->ban[$this->getName()][FormsManager::TIME] === 0);
    }

    public function getBanProfile(): array
    {
        return $this->cache->ban[$this->getName()];
    }

    public function getInventoryId(): int
    {
        return $this->inventoryId ?? 0;
    }

    public function setNick(bool $bool): void
    {
        $this->nick = $bool;
    }

    public function isNick(): bool
    {
        return $this->nick;
    }

    public function setInventoryId(int $id): void
    {
        $this->inventoryId = $id;
    }

    public function getAllowedScoreboard(): bool
    {
        return !isset($this->cache->players[$this->getName()][ICache::SCOREBOARD]) || $this->cache->players[$this->getName()][ICache::SCOREBOARD] !== "false";
    }

    public function setAllowedScoreboard(bool $bool): void
    {
        $bo = $bool ? "true" : "false";
        $this->cache->players[$this->getName()][ICache::SCOREBOARD] = $bo;
        if (!$bool) $this->cache->scoreboards[$this->getName()]?->remove();
        SQLiteDatabase::getInstance()->update("valea", ["scoreboard" => $bo], ["player" => $this->getName()]);
    }

    public function getHit(): int
    {
        return $this->cache->boxing[$this->getName()] ?? 0;
    }

    public function setHit(int $hit = 0): void
    {
        $this->cache->boxing[$this->getName()] = $hit;
    }

    public function incrementHit(): void
    {
        $this->cache->boxing[$this->getName()]++;
    }

    public function getChatCoolodwn(): int
    {
        return $this->chatCooldown;
    }

    public function addChatCooldown(int $time): void
    {
        $this->chatCooldown = time() + $time;
    }

    public function setDeathMessage(bool $bool): void
    {
        $bo = $bool ? "true" : "false";
        SQLiteDatabase::getInstance()->update("valea", ["death_message" => $bo], ["player" => $this->getName()]);
        $this->cache->players[$this->getName()][ICache::DEATH_MESSAGE] = $bo;
    }

    public function getDeathMessage(): bool
    {
        if (!isset($this->cache->players[$this->getName()][ICache::DEATH_MESSAGE])) $this->setDeathMessage(false);
        return $this->cache->players[$this->getName()][ICache::DEATH_MESSAGE] === "true";
    }
    public function spawnTo(\pocketmine\player\Player $player): void
    {
        parent::spawnTo($player);
        
        $pk = SetActorDataPacket::create(
            $this->getId(),
            [EntityMetadataProperties::HEALTH => new IntMetadataProperty(0)],
            new PropertySyncData([], []),
            0
        );
        $player->getNetworkSession()->sendDataPacket($pk);
    }
}
