<?php

namespace Nathan45\Valea\Utils\Interfaces;

interface IMessages
{
    const JOINED_QUEUE       = IUtils::PREFIX . "§aSuccessfully joined the queue!";
    const DUEL_END           = "{looser} was beaten by {killer}{killerHp} in {mode}";
    const USE_SPAWN_COMMAND  = IUtils::PREFIX . "§aSuccessfully teleported to the spawn!";
    const WORLD_SOON_DELETED = IUtils::PREFIX . "The world you were in is going to be deleted";
    const PEARL_COOLDOWN     = "§cYou're in cooldown : {time} sec. remaining";
    const IN_COMBAT          = IUtils::PREFIX . "§cYou're currently in combat : wait {seconds} seconds.";
    const PLAYER_IN_COMBAT   = IUtils::PREFIX . "§c{player} is currently in combat : wait {seconds} seconds";
    const NOT_PERMISSION     = IUtils::ERROR . "§cSorry, you don't have the permission :/";
    const PLAYER_NOT_FOUND   = IUtils::PREFIX . "§cSorry, this player doesn't exist";
    const SUCCESSFUL         = IUtils::PREFIX . "§aSuccessfully";
    const LEAVE_QUEUE        = IUtils::PREFIX . "Successfully left the queue";
    const SET_IN_COMBAT      = IUtils::PREFIX . "You are now in combat with {target}";
    const SET_CPS_COUNTER    = IUtils::PREFIX . "§aSuccessfully changed your cps counter!";
    const SET_POTION         = IUtils::PREFIX . "§aSuccessfully changed your potion type";
    const SET_SCOREBOARD     = IUtils::PREFIX . "§aSuccessfully changed your scoreboard";
    const SET_REQUEUE        = IUtils::PREFIX . "§aSuccessfully changed your automatic requeue";
    const SET_REKIT          = IUtils::PREFIX . "§aSuccessfully changed your automatic rekit";
    const REKIT              = IUtils::PREFIX . "§aYou have been rekitted";
    const RECEIVE_ELO        = IUtils::PREFIX . "§aYou have received {elo} elo, gg";
    const FRIEND_REQUEST     = IUtils::PREFIX . "§6{player}§a sent you a friend request";
    const NO_FRIENDS         = IUtils::PREFIX . "§cLooks like you have no friends, try again later.";
    const NO_FRIEND_REQUESTS = IUtils::PREFIX . "§cYou have no friend request";
    const SET_DEATH_MESSAGE  = IUtils::PREFIX . "§aSuccessfully changed the display of death messages!";
}