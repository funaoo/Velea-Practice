<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;

class VanishCommand extends Command
{
    public function __construct()
    {
        parent::__construct("vanish", "Staff - Vanish Command", "/vanish", ["staffmode", "sm", "staff"]);
        $this->setPermission("valea.vanish");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) return;

        if (!$this->testPermission($sender)) return;

        if ($sender->getGamemode() === GameMode::SURVIVAL) {
            $sender->setGamemode(GameMode::SPECTATOR);
            $sender->sendMessage(TextFormat::GREEN . 'Staff mode enabled');
            return;
        }

        if ($sender->getGamemode() === GameMode::SPECTATOR) {
            $sender->setGamemode(GameMode::SURVIVAL);
            $sender->sendMessage(TextFormat::RED . 'Staff mode disabled');
        } else {
            $sender->sendMessage(TextFormat::RED . 'You must be in survival mode to use this command');
        }
    }
}