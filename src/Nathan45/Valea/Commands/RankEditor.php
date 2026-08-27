<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Utils\FormsManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RankEditor extends Command
{
    public function __construct()
    {
        parent::__construct("editrank", "Valea+ - Edit Rank Command", "/editrank");
        $this->setPermission("valea.rank.edit");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender->hasPermission("valea.rank.edit")) {
            FormsManager::sendRankEditForm($sender);
        } else {
            $sender->sendMessage(TextFormat::RED . "You cannot use this");
        }
    }
}