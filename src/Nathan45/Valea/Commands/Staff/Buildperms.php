<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class Buildperms extends Command
{
    public static array $buildperms = [];

    public function __construct(private Loader $plugin)
    {
        parent::__construct('buildperms', 'Valea - Build Command', '/buildperm');
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) return;

        if (!$this->testPermission($sender)) return;

        if (!isset($args[0])) {
            self::$buildperms[$sender->getName()] = $sender->getName();
            $sender->sendMessage(Utils::PREFIX . TextFormat::GREEN . "You now have build permissions!");
            return;
        }

        $target = $this->plugin->getServer()->getPlayerExact($args[0]);

        if ($target instanceof RPlayer) {
            self::$buildperms[$target->getName()] = $target->getName();
            $sender->sendMessage(Utils::PREFIX . TextFormat::GREEN . "{$target->getName()} now has build perms kick him to remove");
            $target->sendMessage(Utils::PREFIX . TextFormat::GREEN . "{$sender->getName()} gave you build perms");
        } else {
            $sender->sendMessage(Utils::PREFIX . TextFormat::RED . "{$args[0]} is offline");
        }
    }
}