<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class PingCommand extends Command
{
    public function __construct()
    {
        parent::__construct("ping", "Valea - Ping Command", "/ping", ["ms"]);
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer) {
            $sender->sendMessage(IUtils::PREFIX . "§aYour ping > §f" . $sender->getNetworkSession()->getPing());
        }
    }
}