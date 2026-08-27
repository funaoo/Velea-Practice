<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class AutoSprintCommand extends Command
{
    public static array $sprinting = [];

    public function __construct(private Loader $main)
    {
        parent::__construct("autosprint", "Valea - AutoSprint Command", "/autosprint", ["as"]);
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) {
            $sender->sendMessage('§cYou can\'t use this command !');
            return;
        }

        if (self::isInSprintMode($sender->getName())) {
            $sender->sendMessage(IUtils::PREFIX . '§cAutosprint disabled');
        } else {
            $sender->sendMessage(IUtils::PREFIX . '§aAutosprint enabled');
        }

        self::sprint($sender->getName());
    }

    public static function isInSprintMode(string $player): bool
    {
        return isset(self::$sprinting[$player]);
    }

    public static function sprint(string $player): void
    {
        if (isset(self::$sprinting[$player])) {
            unset(self::$sprinting[$player]);
        } else {
            self::$sprinting[$player] = $player;
        }
    }
}