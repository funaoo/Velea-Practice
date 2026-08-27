<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class RulesCommand extends Command
{
    public function __construct()
    {
        parent::__construct("rules", "Valea - Rules Command");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer) {
            (new FormsManager())->sendRulesForm($sender);
        }
    }
}