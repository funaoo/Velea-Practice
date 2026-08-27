<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Events\Event;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class EventStartEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(private Event $event)
    {
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}