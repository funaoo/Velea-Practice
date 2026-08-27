<?php

namespace Nathan45\Valea\Tasks\Async;

use Nathan45\Valea\Utils\Interfaces\IMessages;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\World;

class DeleteWorld extends AsyncTask
{
    public function __construct(private string $dataPath, World $level)
    {
        $this->teleportPlayers($level);
    }

    public function onRun(): void
    {
        $dir = $this->dataPath;
        $this->deleteTree($dir);
        @rmdir($dir);
    }

    public function deleteTree(string $dir): void
    {
        foreach (glob($dir . "/*") as $element) {
            if (is_dir($element)) {
                $this->deleteTree($element);
                rmdir($element);
            } else {
                unlink($element);
            }
        }
    }

    private function teleportPlayers(World $level): void
    {
        foreach ($level->getPlayers() as $player) {
            Server::getInstance()->getCommandMap()->dispatch($player, "spawn");
            $player->sendMessage(IMessages::WORLD_SOON_DELETED);
        }
        Server::getInstance()->getWorldManager()->unloadWorld($level);
    }
}
