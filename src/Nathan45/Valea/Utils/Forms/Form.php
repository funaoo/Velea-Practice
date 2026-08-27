<?php

declare(strict_types=1);

namespace Nathan45\Valea\Utils\Forms;

use Nathan45\Valea\RPlayer;
use pocketmine\form\Form as IForm;
use pocketmine\player\Player;

abstract class Form implements IForm
{
    protected array $data = [];
    private $callable;

    public function __construct(?callable $callable)
    {
        $this->callable = $callable;
    }

    public function sendToPlayer(Player $player): void
    {
        if ($player instanceof RPlayer) {
            if ($player->isFormOpen()) return;
            $player->setFormOpen(true);
        }
        $player->sendForm($this);
    }

    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    public function setCallable(?callable $callable): void
    {
        $this->callable = $callable;
    }

    public function handleResponse(Player $player, mixed $data): void
    {
        if ($player instanceof RPlayer) {
            $player->setFormOpen(false);
        }
        $this->processData($data);
        $callable = $this->getCallable();
        if ($callable !== null) {
            $callable($player, $data);
        }
    }

    public function processData(mixed &$data): void
    {
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}