<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class FreezeCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("freeze", "Staff - Freeze Command");
        $this->setPermission("valea.freeze");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer && $sender->hasPermission(IPermissions::FREEZE)) {
            (new FormsManager())->sendOnlinePlayersForm($sender, FormsManager::FREEZE);
        } else {
            $sender->sendMessage(IMessages::NOT_PERMISSION);
        }
    }
}