<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class RekitCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("rekit", "Valea - Rekit Command", "/rekit");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) {
            $sender->sendMessage("Use this command in game!");
            return;
        }

        if ($sender->isOnCombat() === 0) {
            $sender->sendMessage(IUtils::PREFIX . "§aYou can not use this command in combat!");
            return;
        }

        $level = $sender->getWorld()->getFolderName();

        if (str_contains(strtolower($level), "arena")) {
            $sender->sendMessage(IUtils::PREFIX . "§aYou can not use this command in duel!");
            return;
        }

        $sender->reKit($level);
        $sender->sendMessage(IMessages::REKIT);
    }
}