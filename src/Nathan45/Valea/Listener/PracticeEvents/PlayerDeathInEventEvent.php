<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Events\Event;
use Nathan45\Valea\RPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class PlayerDeathInEventEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(private RPlayer $player, private Event $event, private RPlayer $killer)
    {
    }

    public function getPlayer(): RPlayer
    {
        return $this->player;
    }

    public function getKiller(): RPlayer
    {
        return $this->killer;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}