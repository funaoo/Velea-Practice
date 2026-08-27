<?php

namespace Nathan45\Valea\Tasks\Delayed;

use Nathan45\Valea\Events\Event;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\scheduler\Task;

class EventDelayedTask extends Task
{
    private Loader $plugin;

    public function __construct(private Event $event)
    {
        $this->plugin = Loader::getInstance();
    }

    public function onRun(): void
    {
        $event   = $this->event;
        $players = $event->getPlayers();

        foreach ($players as $player) {
            if (!$player instanceof RPlayer) continue;
            $player->sendMessage(IUtils::PREFIX . "§aThe event will start!");
        }

        $event->setStart();
    }
}