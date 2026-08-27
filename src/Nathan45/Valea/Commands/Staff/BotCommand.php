<?php

namespace Nathan45\Valea\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class BotCommand extends Command
{
    public function __construct()
    {
        parent::__construct("bot", "Staff - Bot Command", "/bot");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
    }
}