<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class BoxingTapEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(public Player $victim, public Player $damager)
    {
    }

    public function getDamager(): Player
    {
        return $this->damager;
    }

    public function getVictim(): Player
    {
        return $this->victim;
    }
}