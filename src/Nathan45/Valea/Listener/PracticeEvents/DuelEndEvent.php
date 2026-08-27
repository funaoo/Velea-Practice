<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Duels\Duel;

class DuelEndEvent extends PracticeEvent
{
    public function __construct(private Duel $duel, private array $winners, private array $losers)
    {
    }

    public function getDuel(): Duel
    {
        return $this->duel;
    }

    public function getLosers(): array
    {
        return $this->losers;
    }

    public function getWinners(): array
    {
        return $this->winners;
    }
}