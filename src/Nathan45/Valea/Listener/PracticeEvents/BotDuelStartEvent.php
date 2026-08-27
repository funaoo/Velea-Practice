<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\RPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class BotDuelStartEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(private RPlayer $player, private Bot $bot)
    {
    }

    public function getPlayer(): RPlayer
    {
        return $this->player;
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }
}