<?php

namespace Nathan45\Valea\Utils\Interfaces;

interface ICache
{
    const LOBBY   = 0;
    const NODEBUFF = 1;
    const GAPPLE  = 2;
    const FIST    = 3;
    const SUMO    = 4;
    const RUSH    = 5;
    const SOUP    = 6;
    const BOXING  = 7;
    const BU      = 8;
    const COMBO   = 9;
    const FINAL   = 10;
    const CAVE    = 11;
    const BRIDGE  = 12;
    const SPLEEF  = 13;

    const COINS        = 0;
    const KILLS        = 1;
    const DEATH        = 2;
    const RANK         = 3;
    const ELO          = 4;
    const CPS          = 5;
    const IP           = 6;
    const ID           = 7;
    const FRIENDS      = 8;
    const INVENTORIES  = 9;
    const SCOREBOARD   = 10;
    const DEATH_MESSAGE = 11;

    const BY_NAME  = 0;
    const TIME_SEC = 1;
    const REASON   = 2;
}