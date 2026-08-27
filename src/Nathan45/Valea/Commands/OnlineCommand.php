<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Loader;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class OnlineCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("online", "Valea - Online Command", "/online");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
        $sender->sendMessage(IUtils::PREFIX . "§aList of online players (" . count($onlinePlayers) . "/" . $this->plugin->getServer()->getMaxPlayers() . ")");
        $names = [];
        foreach ($onlinePlayers as $p) {
            $names[] = $p->getName();
        }
        $sender->sendMessage(implode("§7, §6", $names));
    }
}