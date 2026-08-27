<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;

class UnBanCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("unban", "Staff - UnBan Command", "/unban", ["pardon"]);
        $this->setPermission("valea.unban");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer && $sender->hasPermission(IPermissions::UNBAN)) {
            (new FormsManager())->sendOnlinePlayersForm($sender, FormsManager::UNBAN);
            return;
        }

        if ($sender instanceof ConsoleCommandSender && isset($args[0])) {
            (new Utils())->unban($args[0]);
            return;
        }

        $sender->sendMessage(IMessages::NOT_PERMISSION);
    }
}