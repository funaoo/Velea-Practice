<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class FlyCommand extends Command
{
    public function __construct(private Loader $main)
    {
        parent::__construct("fly", "Staff - Fly Command", "/fly");
        $this->setPermission("valea.fly");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer || !$sender->hasPermission(IPermissions::FLY)) {
            $sender->sendMessage(IMessages::NOT_PERMISSION);
            return;
        }

        $newState = !$sender->getAllowFlight();
        $sender->setAllowFlight($newState);
        $sender->setFlying($newState);
        $sender->sendMessage(IUtils::PREFIX . "§aSuccessfully !");
    }
}