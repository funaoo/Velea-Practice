<?php

namespace Nathan45\Valea\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class InfoCommand extends Command
{
    public function __construct()
    {
        parent::__construct("info", "Valea - Info Command", "/info", ["discord"]);
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $sender->sendMessage("§c------------------------------------");
        $sender->sendMessage("§7§c Store : §7https://valea.tebex.io/");
        $sender->sendMessage("§7§c Discord : §7https://discord.gg/TNPXRsSmnB");
        $sender->sendMessage("§c------------------------------------");
    }
}