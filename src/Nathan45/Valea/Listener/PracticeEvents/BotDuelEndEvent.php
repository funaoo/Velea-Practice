<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\RPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\Server;

class BotDuelEndEvent extends PracticeEvent implements Cancellable
{
    use CancellableTrait;

    public function __construct(private RPlayer $player, private Bot $bot, private ?string $message = null)
    {
        if ($this->message !== null) {
            Server::getInstance()->broadcastMessage($this->message);
        }
    }

    public function getBot(): Bot
    {
        return $this->bot;
    }

    public function getPlayer(): RPlayer
    {
        return $this->player;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}