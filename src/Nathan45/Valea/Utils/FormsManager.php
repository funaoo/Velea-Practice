<?php

namespace Nathan45\Valea\Utils;

use Nathan45\Valea\Discord\Embed;
use Nathan45\Valea\Discord\Message;
use Nathan45\Valea\Discord\Webhook;
use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Entities\Bots\NoDeBuffBot;
use Nathan45\Valea\Entities\Bots\SumoBot;
use Nathan45\Valea\Listener\PracticeEvents\PlayerJoinFfaEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Forms\CustomForm;
use Nathan45\Valea\Utils\Forms\ModalForm;
use Nathan45\Valea\Utils\Forms\SimpleForm;
use Nathan45\Valea\Utils\Interfaces\ICache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Interfaces\IUis;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Rank;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as TE;
use pocketmine\world\Position;

class   FormsManager implements IUtils, IUis
{
    const FREEZE = 0;
    const MUTE = 1;
    const VANISH = 2;
    const RANK = 3;
    const REPORT = 4;
    const FRIENDS = 5;
    const UNBAN = 6;
    const CLEAR_SKIN = 7;

    const BY_NAME = 0;
    const TIME = 1;
    const REASON = 2;

    
    private Loader $plugin;
    
    private Utils $utils;
    
    private Cache $cache;

    
    private array $random = [
        "StevePilou5",
        "Pr0GxmerYT927",
        "Xlqniu7",
        "Pilosere505",
        "olopoth78",
        "Venern1050",
        "Trepanton2004",
        "XxxTentacion8236",
        "Viatonplay828",
        "Elmo4Player6",
        "SuperX65",
        "XxPoloYTzX",
        "Brage00Kelvin",
        "CreeperPlayz34",
        "Steppinghorse7",
        "Xq893",
        "EpicPlayer101",
        "Ana7elo3",
        "ChieckenEater93"
    ];

    
    public array $capes = [
        "Red",
        "Blue",
        "Purple",
        "Black",
    ];

    public function __construct()
    {
        $this->plugin = Loader::getInstance();
        $this->utils = new Utils();
        $this->cache = Cache::getInstance();
    }

    
    public function sendOnlinePlayersForm(RPlayer $player, int $mode = self::FREEZE) : void{

        $players = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $p){
            if($mode !== self::FRIENDS && in_array($p->getName(), $player->getFriends(), true)) continue;
            $players[] = $p->getName();
        }

        if($mode === self::FRIENDS) unset($players[array_search($player, $players, true)]);

        $form = new CustomForm(function (RPlayer $player, $data) use ($players, $mode) {
            if($data === null || !$player instanceof RPlayer) return;

            $target = (empty($data[1])) ? $players[$data[0]] : $data[1];
            $target = Server::getInstance()->getPlayerExact($target);
            if(!$target instanceof RPlayer AND ($mode !== self::RANK) && ($mode !== self::UNBAN)){
                $player->sendMessage(self::ERROR . "§cplayer not found, please try again");
                return;
            }

            switch ($mode){
                case self::FREEZE:
                    if($target->isFreeze()) {
                        $target->unFreeze();
                        $player->sendMessage(self::PREFIX . "§a{$target->getName()} is now unfreeze !");
                        return;
                    }
                    $target->setFreeze(99999999*20, $player);
                    $player->sendMessage(self::PREFIX . "§a{$target->getName()} is now freeze !");
                    break;

                case self::UNBAN:
                    $this->utils->unban((empty($data[1])) ? $players[$data[0]] : $data[1], $player);
                    break;

                case self::MUTE:
                    $target->setMuted(!$target->isMuted());
                    $player->sendMessage($message = (!$target->isMuted()) ? self::PREFIX . "§a{$target->getName()} is now unmute !" : self::PREFIX . "§a{$target->getName()} is now muted !");
                    break;

                case self::VANISH:
                    $player->teleport(new Position($target->getPosition()->x, $target->getPosition()->y + 2, $target->getPosition()->z, $target->getWorld()));
                    $player->sendMessage(self::PREFIX . "§aSuccessful teleported on {$target->getName()}");
                    break;

                case self::RANK:
                    $this->sendRankForm($player,  (empty($data[1])) ? $players[$data[0]] : $data[1]);
                    break;

                case self::REPORT:
                    $this->sendReportPlayerForm($player, $target);
                    break;

                case self::FRIENDS:
                    $player->sendFriendRequestTo($target);
                    break;

                case self::CLEAR_SKIN:
                    $target->setSkin($player->getSkin());
                    $target->sendSkin();
                    $player->sendMessage(IUtils::PREFIX . TextFormat::GREEN . "Players skin reset");
                    $target->sendMessage(IUtils::PREFIX . TextFormat::RED . "Ur skin was reset by a staff member due to having a invalid skin.");
                    break;
            }
        });
        $form->setTitle(IUis::PLAYERS_TITLE);
        $form->addDropdown("Select a player", $players);
        $form->addInput("Or enter her name : ");
        $form->sendToPlayer($player);
    }

    

    public static function sendWelcomeForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){

        });
        $form->setTitle(TextFormat::GOLD . "- Return of Valea -");
        $form->setContent("\n§aAfter we closed a few months ago, we are proud to announce the new start of valea !\n \n§7[§l§dNOTE§r§7] We had to remake everything so if you come across any bugs please report them!\n \n§9§l§dDISCORD§r§7] Feel free to join our discord - discord.gg/valea\n \n§7[§l§dTWITTER§R§7] Follow us on twitter @ValeaNetwork\n \nWe will try to offer the best EU Practice experience, feel free to suggest features, hope you enjoy ur time at valea <3");
    }

    

    public static function sendRankEditForm(RPlayer $player): void
    {
        $rankId = $player->getRank()->getId();

        $allColors = [
            ["code" => "§4", "tf" => TextFormat::DARK_RED,    "name" => "Dark Red"],
            ["code" => "§c", "tf" => TextFormat::RED,          "name" => "Red"],
            ["code" => "§6", "tf" => TextFormat::GOLD,         "name" => "Gold"],
            ["code" => "§e", "tf" => TextFormat::YELLOW,       "name" => "Yellow"],
            ["code" => "§2", "tf" => TextFormat::DARK_GREEN,   "name" => "Dark Green"],
            ["code" => "§a", "tf" => TextFormat::GREEN,        "name" => "Green"],
            ["code" => "§b", "tf" => TextFormat::AQUA,         "name" => "Aqua"],
            ["code" => "§3", "tf" => TextFormat::DARK_AQUA,    "name" => "Dark Aqua"],
            ["code" => "§1", "tf" => TextFormat::DARK_BLUE,    "name" => "Dark Blue"],
            ["code" => "§9", "tf" => TextFormat::BLUE,         "name" => "Blue"],
            ["code" => "§d", "tf" => TextFormat::LIGHT_PURPLE, "name" => "Light Purple"],
            ["code" => "§5", "tf" => TextFormat::DARK_PURPLE,  "name" => "Dark Purple"],
            ["code" => "§f", "tf" => TextFormat::WHITE,        "name" => "White"],
            ["code" => "§7", "tf" => TextFormat::GRAY,         "name" => "Gray"],
            ["code" => "§8", "tf" => TextFormat::DARK_GRAY,    "name" => "Dark Gray"],
            ["code" => "§0", "tf" => TextFormat::BLACK,        "name" => "Black"],
        ];

        $allowedIndexes = match ($rankId) {
            Rank::RANK_VALEA                     => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
            Rank::RANK_YOUTUBE                   => [1, 2, 3, 10, 11, 12],
            Rank::RANK_BUILDER                   => [4, 5, 9, 13, 14],
            Rank::RANK_DEVELOPER                 => [6, 7, 8, 9, 10, 11],
            Rank::RANK_HELPER                    => [4, 5],
            Rank::RANK_TMOD                      => [4],
            Rank::RANK_MOD                       => [6, 7],
            Rank::RANK_SRMOD                     => [8],
            Rank::RANK_ADMIN                     => [1],
            Rank::RANK_MANAGER, Rank::RANK_OWNER => [0, 1],
            default                              => [],
        };

        if (empty($allowedIndexes)) {
            $player->sendMessage(IUtils::PREFIX . "§cYou don't have access to rank color customization.");
            return;
        }

        $availableColors = array_values(array_intersect_key($allColors, array_flip($allowedIndexes)));

        $form = new SimpleForm(function (RPlayer $player, $data) use ($availableColors) {
            if ($data === null || !$player instanceof RPlayer) return;
            if (!isset($availableColors[$data])) return;

            $color    = $availableColors[$data];
            $rankName = $player->getRank()->getName();
            $tag      = "§f[" . $color["code"] . $rankName . "§f] " . $color["code"] . $player->getName();

            $player->setNameTag($tag);
            $player->sendMessage("§aYou changed your rank color to " . $color["tf"] . $color["name"] . "§a!");
        });

        $form->setTitle(TextFormat::GOLD . "Edit rank color");
        foreach ($availableColors as $color) {
            $form->addButton(
                "§f[" . $color["tf"] . $player->getRank()->getName() . TextFormat::WHITE . "] " .
                $color["tf"] . $player->getName() . TextFormat::GRAY . "\nClick Me!"
            );
        }
        $form->sendToPlayer($player);
    }

    

    public function sendSocialMenuForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch ($data){
                case 0:
                    break;

                case 1:
                    $this->sendFriendForm($player);
                    break;
            }

            $player->sendMessage("§8soon...");
        });
        $form->setTitle(self::SOCIAL_MENU_TITLE);
        $form->setContent(self::SOCIAL_MENU_CONTENT);
        $form->addButton("Clans", 0, "textures/ui/empty_armor_slot_shield.png");
        $form->addButton("Friends", 0, "textures/gui/newgui/Friends.png");
        $form->sendToPlayer($player);
    }

    public function sendFriendForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch ($data){
                case 0:
                    $this->sendFriendListForm($player);
                    break;

                case 1:
                    $this->sendOnlinePlayersForm($player, self::FRIENDS);
                    break;

                case 2:
                    $this->sendFriendRequestForm($player);
                    break;
            }
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(self::FRIEND_MENU_CONTENT);
        $form->addButton("Friends list", 0, "textures/gui/newgui/Friends.png");
        $form->addButton("Add a friend", 0, "textures/ui/plus.png");
        $form->addButton("Friend requests\n§7(" . count($player->getFriendsRequests()) . ")", 0, "textures/ui/icon_setting.png");
        $form->sendToPlayer($player);
    }

    public function sendFriendRequestForm(RPlayer $player): void{
        if(count($player->getFriendsRequests()) === 0){
            $player->sendMessage(IMessages::NO_FRIEND_REQUESTS);
            return;
        }

        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            $this->sendAcceptOrDenyRequestForm($player, $data);
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(self::FRIEND_MENU_CONTENT);
        foreach ($player->getFriendsRequests() as $requestor){
            $form->addButton("§a" . $requestor, -1, "", $requestor);
        }
        $form->sendToPlayer($player);
    }

    public function sendAcceptOrDenyRequestForm(RPlayer $player, string $requestor): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use($requestor){
            if($data === null || !$player instanceof RPlayer) return;

            if($data === 0){
                $player->addFriend($requestor);
            }else{
                $player->removeRequest($requestor);
            }
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent("Do you want to accept or refuse {$requestor}'s friend request?");
        $form->addButton("§aAccept");
        $form->addButton("§cRefuse");
        $form->sendToPlayer($player);
    }

    public function sendFriendListForm(RPlayer $player): void{
        if(count($player->getFriends()) === 0){
            $player->sendMessage(IMessages::NO_FRIENDS);
            return;
        }
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            $this->getProfileForm($player,$data);
        });
        $form->setTitle(self::FRIEND_MENU_TITLE);
        $form->setContent(self::FRIEND_MENU_CONTENT);
        foreach ($player->getFriends() as $friend){
            $form->addButton($friend, -1, "", $friend);
        }
        $form->sendToPlayer($player);
    }

    

    public function sendRankForm(RPlayer $player, string $target): void{
        $utils = new Utils();
        if(!$utils->accountExist($target)){
            $player->sendMessage(IMessages::PLAYER_NOT_FOUND);
            return;
        }

        $form = new CustomForm(function (RPlayer $player, $data) use($target, $utils){
           if($data === null) return;

           $utils->setRank($target, $data[0]);
           $player->sendMessage(IMessages::SUCCESSFUL);
        });

        $form->setTitle(str_replace("{target}", $target, self::RANK_TITLE));
        $form->addDropdown(self::RANK_CONTENT, $utils->getAllRanks());
        $form->sendToPlayer($player);
    }

    

    public function openTagForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){;
            if($data === null || !$player instanceof RPlayer) return;

            if($data === "remove"){
                $player->setNameTag($player->getRank()->toString() . $player->getDisplayName());
                $player->sendMessage(IUtils::PREFIX . 'Tag has been cleared');
                return;
            }

            $player->setNameTag($data . "§r| " . $player->getRank()->toString() . $player->getDisplayName());
            $player->sendMessage(IUtils::PREFIX . "Tag updated to " . $data);

        });
        $form->setTitle(self::TAGS_TITLE);
        $form->setContent(self::TAGS_CONTENT);
        $form->setContent(self::TAGS_CONTENT);
        $form->addButton('§4King', -1, '', "§4King");
        $form->addButton('§dQueen', -1, "", "§dQueen");
        $form->addButton('§cRemove Tag', -1, '', "remove");
        $form->sendToPlayer($player);
    }

    

    public function openFfaForm(RPlayer $player) : void
    {
        $form = new SimpleForm(function (RPlayer $player, $data) {
            if ($data === null || !$player instanceof RPlayer || $data === "soon") return;

            $player->removeQueue();
            $event = new PlayerJoinFfaEvent($player, $data);
            $event->call();
            if (!$event->isCancelled()) {
                $this->utils->joinFfa($player, $data);
            }
        });
        $form->setTitle(self::FFA_FORM_TITLE);
        $form->setContent(self::FFA_FORM_CONTENT);
        $wm = $this->plugin->getServer()->getWorldManager();
        $form->addButton("Rush\n§7Playing : " . count(($wm->getWorldByName(IUtils::RUSH_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/bed_red.png", "Rush");
        $form->addButton("Soup\n§7Playing : " . count(($wm->getWorldByName(IUtils::SOUP_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/mushroom_stew.png", "Soup");
     
        $form->addButton("Gapple\n§7Playing : " . count(($wm->getWorldByName(IUtils::GAPPLE_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/apple_golden.png", "Gapple");
        $form->addButton("NoDeBuff\n§7Playing : " . count(($wm->getWorldByName(IUtils::NODEBUFF_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/potion_bottle_splash_heal.png", "NoDeBuff");
        $form->addButton("Sumo\n§7Playing : " . count(($wm->getWorldByName(IUtils::SUMO_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/fish_cooked.png", "Sumo");
        $form->addButton("Fist\n§7Playing : " . count(($wm->getWorldByName(IUtils::FIST_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/beef_cooked.png", "Fist");
        $form->addButton("Combo\n§7Playing : " . count(($wm->getWorldByName(IUtils::COMBO_FFA_WORLD_NAME)?->getPlayers() ?? [])), 0, "textures/items/fish_pufferfish_raw.png", "Combo");
        $form->sendToPlayer($player);
    }

    

    public function openDuelForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch($data){
                case 0:
                    $this->sendNewDuelForm($player, true, 2);
                    break;

                case 1:
                    $this->sendNewDuelForm($player, false, 2);
                    break;

                case 2:
                    
                    break;

                case 3:
                    
                    break;
            }
        });
        $form->setTitle(self::DUEL_FORM_TITLE);
        $form->setContent(self::DUEL_FORM_CONTENT);
        $form->addButton("Ranked\n§7" . $this->cache->getRankedDuels(), 0, "textures/items/diamond_sword.png");
        $form->addButton("Unranked\n§7" . $this->cache->getRankedDuels(false), 0, "textures/items/iron_axe.png");
        $form->addButton("Spectate\n§7soon...", 0, "textures/items/ender_eye.png");
        $form->addButton("Duels Log\n§7soon...", 0, "textures/items/paper.png");
        $form->sendToPlayer($player);
    }

    public function sendPlayerCounterForDuelForm(RPlayer $player, bool $ranked = false): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use ($ranked){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;

            if($data === 4) $this->openDuelForm($player);
            else $this->sendNewDuelForm($player, $ranked, $data * 2);
        });
        $form->setTitle(self::DUEL_FORM_TITLE);
        $form->setContent(self::DUEL_FORM_CONTENT);
        $form->addButton("1v1\n§7" . $this->cache->getPlayersInDuel($ranked) . " players", 0, "textures/1v1.png", 1);
        $form->addButton("2v2\n§7soon..", 0, "textures/gui/newgui/Friends.png", "soon");
        $form->addButton("3v3\n§7soon...", 0, "textures/3v3.png", "soon");
        $form->sendToPlayer($player);
    }

    public function sendNewDuelForm(RPlayer $player, bool $ranked, int $players = 2): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use ($ranked, $players){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;
            $this->utils->addInQueue($player, $ranked, $players, $data);

        });
        $form->setTitle(self::DUEL_FORM_TITLE);
        $form->setContent(self::DUEL_FORM_CONTENT);
        $form->addButton("NoDeBuff\n§7Queue : " . $this->cache->getDuel($ranked, $players, "nodebuff"), 0, "textures/items/potion_bottle_splash_heal.png", "NoDeBuff");
        $form->addButton("Gapple\n§7Queue : " . $this->cache->getDuel($ranked, $players, "gapple"), 0, "textures/items/apple_golden.png", "Gapple");
        $form->addButton("Fist\n§7Queue : " . $this->cache->getDuel($ranked, $players, "fist"), 0, "textures/items/beef_cooked.png", "Fist");
        $form->addButton("Sumo\n§7Queue : " . $this->cache->getDuel($ranked, $players, "sumo"), 0, "textures/items/fish_cooked.png", "Sumo");
        $form->addButton("Build UHC\n§7Queue : " . $this->cache->getDuel($ranked, $players, "build"), 0, "textures/items/bucket_lava.png", "Build");
        
      
     
        $form->sendToPlayer($player);
    }

    public function nickNameForm(RPlayer $player):void{
        $form = new SimpleForm(function (RPlayer $player, $data) {
            if($data === null || !$player instanceof RPlayer) return;
            switch($data){
                case 0:
                    $nick = $this->random[array_rand($this->random)];
                    $player->setNameTag(TextFormat::GREEN . $nick);
                    $player->sendMessage("You are now nicked as {$nick}");
                    break;
                case 1:
                    self::sendRenameForm($player);
                    break;
                case 2:
                    $player->setNameTag($player->getRank()->toString() . $player->getName());;
                    $player->sendMessage("You reset ur nickname");
                    break;
            }
        });

        $form->setTitle(IUis::NICK_TITLE);
        $form->addButton("Random");
        $form->addButton("Custom");
        $form->addButton("Clear");
        $form->sendToPlayer($player);
    }

    public function displayForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;
            switch($data){
                case 0:
                    $player->setCpsCounter(($player->getCpsCounter() === "true") ? 'false' : 'true');
                    $player->sendMessage(IMessages::SET_CPS_COUNTER);
                    break;
                case 1:
                    $player->setAllowedScoreboard(!$player->getAllowedScoreboard());
                    if($player->getAllowedScoreboard()) $player->getScoreboard();
                    break;

                case 2:
                    $player->setDeathMessage(!$player->getDeathMessage());
                    $player->sendMessage(IMessages::SET_DEATH_MESSAGE);
                    break;
            }
        });

        $form->setTitle("§7- §cDisplay §7-");
        $form->addButton("CPS Counter: " . (($player->getCpsCounter() === "true") ? "Enabled" : "Disabled") . "\n§7" . (($player->getCpsCounter() === "true") ? "Click to disable it" : "Click to enable it"));
        $form->addButton("Scoreboard: " . (($player->getAllowedScoreboard()) ? "Enabled" : "Disabled") . "\n§7" . (($player->getAllowedScoreboard()) ? "Click to disabled it" : "Click to enable it"));
        $form->addButton("Display death messages: " . (($player->getDeathMessage()) ? "Enabled" : "Disabled") . "§7\n§7Click to " . (($player->getDeathMessage()) ? "disable" : "enable") . " it");
        $form->sendToPlayer($player);
    }

    public function privacyForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;
            switch ($data){
                case 0:
                    $this->nickNameForm($player);
                    break;
                case 1:
                    $player->chat("/editrank");
                    break;
                case 2:
                    $this->sendChooseCapeForm($player);
                    break;
            }
        });
        $form->setTitle("§7- §cPlayer §7-");
        $form->addButton("Disguise ");
        $form->addButton("Modify Rank");
        $form->addButton("Cosmetics");
        $form->sendToPlayer($player);
    }

    public function gamePlayForm(RPlayer $player){
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;
            switch ($data){
                case 0:
                    $player->chat("/autosprint");
                    break;
                case 1:
                    $player->sendMessage("Coming soon!");
                    break;
            }
        });
        $form->setTitle("§7- §cGameplay §7-");
        $form->addButton("Autosprint");
        $form->addButton("Particles");
        $form->sendToPlayer($player);
    }

    

    public function cosmeticsForm(RPlayer $player) :void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer) return;

            switch ($data){

                case 0:
                    $this->displayForm($player);
                    break;
                case 1:
                    $this->gamePlayForm($player);
                    break;
                case 2:
                    $this->privacyForm($player);
                    break;
            }
        });
        $form->setTitle(self::COSMETICS_TITLE);
        $form->setContent(self::COSMETICS_CONTENT);
        $form->addButton("Display"); 
        $form->addButton("Gameplay"); 
        $form->addButton("Player"); 
        $form->sendToPlayer($player);
    }

    private static function sendRenameForm(RPlayer $player) : void{
        $form = new CustomForm(function (RPlayer $player, $data) {
            if($data === null OR !$player instanceof RPlayer) return;

            if($data[0] == null) {
                $player->sendMessage(self::PREFIX . "§cYou must enter a name.");
                self::sendRenameForm($player);
            } else {
                if ($player->hasPermission("OP")) {
                    $player->setDisplayName($data[0]);
                    $player->sendMessage(self::PREFIX . "§aYou renamed as " . $data[0]);
                    $player->setNick(true);
                    $player->setNameTag($player->getRank()->toString() . ' ' . $player->getDisplayName());
                } else {
                    $player->sendMessage(TextFormat::RED . "You dont have permission to use this");
                }
            }
        });
        $form->setTitle(IUis::CUSTOM_NICK_TITLE);
        $form->addInput("Choose a custom nickname:", "Nickname here");
        $form->sendToPlayer($player);
    }

    public function sendChooseCapeForm(RPlayer $player) : void {
        $form = new SimpleForm(function (RPlayer $player, $data) {
            if($data === null OR !$player instanceof RPlayer) return;
            $player->setCape($data);
        });
        $form->setTitle(IUis::CAPES_TITLE);
        $form->setContent(IUis::CAPES_CONTENT);
        foreach ($this->capes as $cape){
            $form->addButton($cape, -1, "", $cape);
        }
        $form->sendToPlayer($player);
    }

    

    
    public function getProfileForm(RPlayer $player, string|RPlayer $target): void{
        if($target instanceof RPlayer) $target = $target->getName();
        if(!isset($this->cache->players[$target])){
            $player->sendMessage(IUtils::PREFIX . TE::RED . "This player does not exist!");
            return;
        }

        $form = new CustomForm(function (RPlayer $player, $data){});
        $form->setTitle(str_replace("{player}", $target, self::PROFILE_TITLE));
        $array = $this->cache->players[$target];
        $form->addLabel("§7>>" . TE::EOL .
            TE::GOLD . "Coins: " . TE::YELLOW . $array[ICache::COINS] . TE::EOL .
            TE::GOLD . "Kills: " . TE::YELLOW . $array[ICache::KILLS] . TE::EOL .
            TE::GOLD . "Death: " . TE::YELLOW . $array[ICache::DEATH] . TE::EOL .
            TE::GOLD . "Rank: " . TE::YELLOW . $this->utils->getRank($target, null)->toString() . TE::EOL .
            TE::GOLD . "Elo: " . TE::YELLOW . $array[ICache::ELO] . TE::EOL);
        $form->sendToPlayer($player);
    }

    

    public function sendReportPlayerForm(RPlayer $player, RPlayer $target): void{
        $array = [
            "Comms Abuse - Text",
            "Cheating",
            "Offensive or inappropriate Name|Skin",
            "Disrespectful Behavior",
            "Threats",
            "Other"
        ];
        $form = new CustomForm(function (RPlayer $player, array $data = null) use ($target, $array){
            if($data === null || !$player instanceof RPlayer || !$target instanceof RPlayer) return;

            $reason = $array[$data[0]];
            $text = $data[1];
            $player->sendMessage(IUtils::PREFIX . "You help us to improve this server, thank you!");

            $msg = new Message();
            $embed = new Embed();
            $msg->setUsername($player->getName());
            $embed->setTitle("Player reported");
            $embed->setColor(self::PURPLE);
            $embed->setFooter(date('l jS \of F Y h:i:s A'));
            $embed->setDescription($player->getName() . " has reported " . $target->getName() . " for " . $reason . " Details : " . $text);
            $msg->addEmbed($embed);
            (new Webhook(IUtils::REPORT_PLAYER_WEBHOOK))->send($msg);
        });
        $form->setTitle(self::BASIC_REPORT_TITLE);
        $form->addDropdown("Select :", $array);
        $form->addInput("be more precise", "Thank you!", "no details");
        $form->sendToPlayer($player);
    }

    

    public function sendBotForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;
            $this->sendTypeBotForm($player, $data);
        });
        $form->setTitle(self::BOT_TITLE);
        $form->setContent(self::BOT_CONTENT);
        $form->addButton("NoDeBuff", -1, "textures/items/potion_bottle_splash_heal.png", "NoDeBuff");
        $form->addButton("Sumo", -1, "textures/items/fish_cooked.png", "Sumo");
        $form->addButton("Gapple\n§7soon...", -1, "textures/items/apple_golden.png", "soon");
        $form->addButton("Fist\n§7soon...", -1, "textures/items/beef_cooked.png", "soon");
        $form->sendToPlayer($player);
    }

    public function sendTypeBotForm(RPlayer $player, string $mode): void{
        $form = new SimpleForm(function (RPlayer $player, $data) use ($mode){
            if($data === null or !$player instanceof RPlayer) return;

            switch ($data){
                case 5:
                    $this->sendCustomBotForm($player, $mode);
                    break;

                default:
                    $skin = new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom", "{}");
                    $location = new Location($player->getPosition()->x - 10, $player->getPosition()->y, $player->getPosition()->z - 10, $player->getWorld(), 0.0, 0.0);
                    if(strtolower($mode) === "nodebuff") $entity = new NoDeBuffBot($location, $skin, null, $player, "§cValea §fBot", $data);
                    else $entity = new SumoBot($location, $skin, null, $player, "§cValea §fBot", $data);
                    if(!$entity instanceof Bot) return;
                    $this->utils->startBotDuel($player, $entity, $mode);
            }

        });
        $form->setTitle(self::TYPE_BOT_TITLE);
        $form->setContent(self::TYPE_BOT_TITLE);
        $form->addButton("§aEasy", -1, "", 1);
        $form->addButton("§6Medium", -1, "", 2);
        $form->addButton("§cHard", -1, "", 3);
        $form->addButton("§4Hacker", -1, "", 4);
        $form->addButton("§bCustomizable", -1, "", 5);
        $form->sendToPlayer($player);
    }

    public function sendCustomBotForm(RPlayer $player, string $mode): void{
        $form = new CustomForm(function(RPlayer $player, $data) use ($mode){
            if($data === null || !$player instanceof RPlayer || $data === "soon") return;

            $skin = new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom", "{}");
            $location = new Location($player->getPosition()->x - 10, $player->getPosition()->y, $player->getPosition()->z - 10, $player->getWorld(), 0.0, 0.0);
            if(strtolower($mode) === "nodebuff") $entity = new NoDeBuffBot($location, $skin, null, $player, "§cValea §fBot", Bot::CUSTOM, $data);
            else $entity = new SumoBot($location, $skin, null, $player, "§cValea §fBot", Bot::CUSTOM, $data);
            if(!$entity instanceof Bot) return;
            $this->utils->startBotDuel($player, $entity, $mode);
        });
        $form->setTitle(self::CUSTOM_BOT_TITLE);
        $form->addSlider("Reach (1 - 10)", 1, 10, 1, 3, "reach");
        $form->addSlider("Health (10 -30)", 10, 30, 2, 20, "health");
        $form->addSlider("Accuracy (1 - 100)", 1, 100, 1, 50, "accuracy");
        $form->addSlider("Damage (1 - 20)", 1, 20, 1, 8, "damage");
        $form->sendToPlayer($player);
    }

    

    public function sendInventoriesForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, int $data = null){
            if($data === null or !$player instanceof RPlayer) return;
            $inventory = $data + 1;
            $player->setFreeze(99999*60);
            $inventories = new Inventories();
            $player->getInventory()->setContents($inventories->getInventory($inventory));
            $player->sendMessage("§cWhen you are done, run the command §f/inventory set§c, if you want to reset your inventory, run §f/inventory reset");
            $player->setInventoryId($inventory);
        });
        $form->setTitle(self::INVENTORIES_TITLE);
        $form->setContent(self::INVENTORIES_CONTENT);
        $form->addButton("NoDeBuff", 0, "textures/items/potion_bottle_splash_heal.png");
        $form->addButton("Gapple", 0, "textures/items/apple_golden.png");
        $form->addButton("Sumo", 0, "textures/items/fish_cooked.png");
        $form->addButton("Fist", 0, "textures/items/beef_cooked.png");
        $form->addButton("Rush", 0, "textures/items/bed_red.png");
        $form->addButton("Soup", 0, "textures/items/mushroom_stew.png");
        $form->addButton("Boxing", 0, "textures/items/diamond_sword.png");
        $form->addButton("Build UHC", 0, "textures/items/bucket_lava.png");
        $form->addButton("Combo", 0, "textures/items/fish_pufferfish_raw.png");
        $form->sendToPlayer($player);
    }

    

    public function sendRulesForm(RPlayer $player): void{
        $form = new SimpleForm(function (RPlayer $player, $data){
            if($data === null || $data === 1) $this->sendRulesForm($player);
        });
        $form->setTitle(self::RULES_TITLE);
        $form->setContent(self::RULES_CONTENT);
        $form->addButton("§aI agree", 0, "textures/ui/check.png");
        $form->addButton("§cI don't agree", 0, "textures/ui/crossout.png");
        $form->sendToPlayer($player);
    }
}