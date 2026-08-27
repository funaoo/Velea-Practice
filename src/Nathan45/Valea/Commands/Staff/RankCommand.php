<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class RankCommand extends Command
{
    public function __construct()
    {
        parent::__construct("rank", "Staff - Rank Command", "/rank");
        $this->setPermission("valea.rank");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender->hasPermission(IPermissions::RANK)) {
            $sender->sendMessage("§cYou don't have permission to use this command.");
            return;
        }

        if ($sender instanceof RPlayer) {
            (new FormsManager())->sendOnlinePlayersForm($sender, FormsManager::RANK);
            return;
        }

        if (isset($args[0]) && isset($args[1])) {
            (new Utils())->setRank($args[0], (int) $args[1]);
        }
    }
}