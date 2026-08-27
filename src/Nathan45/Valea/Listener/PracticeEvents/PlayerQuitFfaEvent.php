<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\RPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class PlayerQuitFfaEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(private RPlayer $player, private string $ffa)
    {
    }

    public function getFfa(): string
    {
        return $this->ffa;
    }

    public function getPlayer(): RPlayer
    {
        return $this->player;
    }
}