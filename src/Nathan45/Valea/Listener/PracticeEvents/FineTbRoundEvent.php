<?php

namespace Nathan45\Valea\Listener\PracticeEvents;

use Nathan45\Valea\Duels\Duel;
use Nathan45\Valea\RPlayer;

class FineTbRoundEvent extends PracticeEvent
{
    public function __construct(private RPlayer $looser, private RPlayer $winner, private int $round, private Duel $duel)
    {
    }

    public function getDuel(): Duel
    {
        return $this->duel;
    }

    public function getLooser(): RPlayer
    {
        return $this->looser;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function getWinner(): RPlayer
    {
        return $this->winner;
    }
}