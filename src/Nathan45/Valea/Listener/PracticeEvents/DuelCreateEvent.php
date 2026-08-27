<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Duels\Duel;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class DuelCreateEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(private Duel $duel)
    {
    }

    public function getDuel(): Duel
    {
        return $this->duel;
    }
}