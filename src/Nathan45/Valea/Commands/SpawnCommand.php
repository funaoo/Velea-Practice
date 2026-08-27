<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Listener\PracticeEvents\PlayerQuitFfaEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Scoreboards\Scoreboard;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;

class SpawnCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("spawn", "Valea - Spawn Command", "/spawn", ["lobby", "hub"]);
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) return;

        $lName = $sender->getWorld()->getFolderName();

        foreach (["NoDeBuffFFA", "GappleFFA", "FistFFA", "SumoFFA", "RushFFA", "BoxingFFA", "SoupFFA"] as $m) {
            if (str_contains($lName, $m)) {
                (new PlayerQuitFfaEvent($sender, $lName))->call();
            }
        }

        if ($sender->getWorld()->getFolderName() !== IUtils::LOBBY_WORLD_NAME) {
            $sender->sendMessage(IMessages::USE_SPAWN_COMMAND);
        }

        $worldManager = $this->plugin->getServer()->getWorldManager();

        if (!$worldManager->isWorldLoaded(IUtils::LOBBY_WORLD_NAME)) {
            $worldManager->loadWorld(IUtils::LOBBY_WORLD_NAME);
        }

        $world = $worldManager->getWorldByName(IUtils::LOBBY_WORLD_NAME);
        $sender->teleport(new Position(IUtils::X_SPAWN, IUtils::Y_SPAWN, IUtils::Z_SPAWN, $world));
        $sender->reKit("Lobby");
        $sender->setInventoryId(0);

        if ($sender->getAllowedScoreboard()) {
            Cache::getInstance()->scoreboards[$sender->getName()] = new Scoreboard($sender, Scoreboard::SCOREBOARD_LOBBY);
        }

        $sender->getEffects()->clear();
        $sender->setHit();
    }
}