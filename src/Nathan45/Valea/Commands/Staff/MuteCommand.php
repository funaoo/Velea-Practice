<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class MuteCommand extends Command
{
    public function __construct()
    {
        parent::__construct("mute", "Staff - Mute Command");
        $this->setPermission("valea.mute");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer && $sender->hasPermission(IPermissions::MUTE)) {
            (new FormsManager())->sendOnlinePlayersForm($sender, FormsManager::MUTE);
        } else {
            $sender->sendMessage(IMessages::NOT_PERMISSION);
        }
    }
}