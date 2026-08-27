<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class WhoCommand extends Command
{
    private Cache $cache;

    public function __construct()
    {
        parent::__construct("who", "Staff - Who Command", "/who <player:string>", ["alias"]);
        $this->setPermission("valea.who");
        $this->cache = Cache::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer || !$sender->hasPermission(IPermissions::WHO)) {
            $sender->sendMessage(IMessages::NOT_PERMISSION);
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage($this->usageMessage);
            return;
        }

        if (isset($this->cache->players[$args[0]])) {
            $arr = $this->cache->players[$args[0]];
            $sender->sendMessage("§7-------" . $args[0] . "'s Profile-------");
            $sender->sendMessage("§6Coins > §e" . $arr[0]);
            $sender->sendMessage("§6Kills > §e" . $arr[1]);
            $sender->sendMessage("§6Death > §e" . $arr[2]);
            $sender->sendMessage("§6Rank > §e" . $arr[3]);
            $sender->sendMessage("§6Elo > §e" . $arr[4]);
            $sender->sendMessage("§6Id > §e" . $arr[6]);
            $sender->sendMessage("§6Ip > §e" . $arr[7]);
            $sender->sendMessage("-------------------------------------------");
            return;
        }

        if (isset($this->cache->ban[$args[0]])) {
            $arr = $this->cache->ban[$args[0]];
            $sender->sendMessage("§7-------" . $args[0] . "'s Profile-------");
            $sender->sendMessage("§6Banned by > §e" . $arr[0]);
            $sender->sendMessage("§6For §e" . $arr[1] / 86400 . " days");
            $sender->sendMessage("§6For reason > §e" . $arr[2]);
            $sender->sendMessage("-------------------------------------------");
            return;
        }

        $sender->sendMessage(IMessages::PLAYER_NOT_FOUND);
    }
}