<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class MeCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("me", "Staff - Me Command", "/me");
        $this->setPermission("valea.me");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer || !$sender->hasPermission(IPermissions::ME)) {
            $sender->sendMessage(IMessages::NOT_PERMISSION);
            return;
        }

        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $player->teleport($sender->getPosition());
        }
    }
}