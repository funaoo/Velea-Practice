<?php

namespace Nathan45\Valea\Utils\Interfaces;

interface IUtils
{
    const PREFIX = "§7[§6VALEA§7] ";
    const ERROR  = "§7[§3VALEA§7] ";

    const PEARL_COOLDOWN   = 15;
    const IP               = "play.valeanetwork.eu";
    const BOXING_HIT_TO_WIN = 100;
    const TB_ROUND_FOR_WIN = 5;

    const BAN_WEBHOOK           = "https://discord.com/api/webhooks/910205439210291250/SxL0HtIROli65vkxQJ1aXjFK6qNjzQLJz9GAykmGDhhOOtC9KtS7Vl1MM25qQHr-xoIh";
    const RANK_WEBHOOK          = "https://discord.com/api/webhooks/910205768291205131/Gb6_y_2jQAsMMXLRJIpP-2mDluPU1iHdLj0ozMZ4fReKWbWdg2AYqw7NBNI17D9LJv_v";
    const UNBAN_WEBHOOK         = "https://discord.com/api/webhooks/910205623688368128/AJCM7-feJ66H9QfC2EYYcWaiHpp5tXkE_bM2bWB5BLJArGO6se3N-6nlpTvZCuHUkdAN";
    const REPORT_WEBHOOK        = "https://discord.com/api/webhooks/926435507066523720/O76w52nNGe9QgpG0Wfpvg_8pXb6orY5uGxSZtqQefV55sV0RLT2XysxMhXJvDRwITuU5";
    const REPORT_PLAYER_WEBHOOK = "https://discord.com/api/webhooks/910206127352995840/gxPmKvExEY5XNIv4fyoRSrhLVxw9lKozQdjCT6kqz1hN2b4r3gSIjy6JqsoTNYI9E365";
    const SKIN_WEBHOOK          = "https://discord.com/api/webhooks/910206127352995840/gxPmKvExEY5XNIv4fyoRSrhLVxw9lKozQdjCT6kqz1hN2b4r3gSIjy6JqsoTNYI9E365";

    const WHITE        = 16777215;
    const GREEN        = 0x32CD32;
    const RED          = 0xFF0000;
    const LIGHT_BLUE   = 0x87CEFA;
    const BLUE         = 0x0000FF;
    const PURPLE       = 0x5440cd;
    const LIGHT_PURPLE = 0xFF00FF;

    const KB_X = 0.4;
    const KB_Y = 0.4;
    const KB_Z = 0.4;

    const LOBBY_WORLD_NAME = "hub";
    const X_SPAWN          = -13;
    const Y_SPAWN          = 90;
    const Z_SPAWN          = 9;

    const NODEBUFF_EVENT_WORLD_NAME = "NoDeBuffEvent";

    const NODEBUFF_DUEL_WORLD_NAME = "NodebuffDuels";
    const GAPPLE_DUEL_WORLD_NAME   = "GappleDuels";
    const FIST_DUEL_WORLD_NAME     = "FistDuels";
    const SUMO_DUEL_WORLD_NAME     = "SumoDuels";
    const BUILD_DUEL_WORLD_NAME    = "GappleDuels";
    const FINAL_DUEL_WORLD_NAME    = "NodebuffDuels";
    const CAVE_DUEL_WORLD_NAME     = "CaveDuels";
    const BRIDGE_DUEL_WORLD_NAME   = "BridgeDuels";
    const BOXING_DUEL_WORLD_NAME   = "BoxingDuels";
    const SPLEEF_DUEL_WORLD_NAME   = "SpleefDuels";

    const RUSH_FFA_WORLD_NAME = "rush";
    const RUSH_FFA_X          = 0;
    const RUSH_FFA_Y          = 90;
    const RUSH_FFA_Z          = 0;

    const COMBO_FFA_WORLD_NAME = "Combo";
    const COMBO_FFA_X          = 256;
    const COMBO_FFA_Y          = 70;
    const COMBO_FFA_Z          = 256;

    const BU_FFA_WORLD_NAME = "Build";
    const BU_FFA_X          = 256;
    const BU_FFA_Y          = 70;
    const BU_FFA_Z          = 256;

    const SOUP_FFA_WORLD_NAME = "Soup";
    const SOUP_FFA_X          = 256;
    const SOUP_FFA_Y          = 70;
    const SOUP_FFA_Z          = 256;

    const BOXING_FFA_WORLD_NAME = "Boxing";
    const BOXING_FFA_X          = 256;
    const BOXING_FFA_Y          = 70;
    const BOXING_FFA_Z          = 256;

    const NODEBUFF_FFA_WORLD_NAME = "Nodebuff";
    const NODEBUFF_FFA_X          = 256;
    const NODEBUFF_FFA_Y          = 70;
    const NODEBUFF_FFA_Z          = 256;

    const GAPPLE_FFA_WORLD_NAME = "Gapple";
    const GAPPLE_FFA_X          = 256;
    const GAPPLE_FFA_Y          = 70;
    const GAPPLE_FFA_Z          = 256;

    const FIST_FFA_WORLD_NAME = "Fist";
    const FIST_FFA_X          = 256;
    const FIST_FFA_Y          = 70;
    const FIST_FFA_Z          = 256;

    const SUMO_FFA_WORLD_NAME = "Sumo";
    const SUMO_FFA_X          = 256;
    const SUMO_FFA_Y          = 70;
    const SUMO_FFA_Z          = 256;

    const BANNED_WORDS = [
        "nigger",
        "nigga",
    ];

    const EVENT_GAPPLE   = "GappleEvent";
    const EVENT_GAPPLE_X = 0;
    const EVENT_GAPPLE_Y = 70;
    const EVENT_GAPPLE_Z = 0;

    const EVENT_NODEBUFF   = "NodebuffEvent";
    const EVENT_NODEBUFF_X = 0;
    const EVENT_NODEBUFF_Y = 70;
    const EVENT_NODEBUFF_Z = 0;

    const EVENT_SUMO   = "SumoEvent";
    const EVENT_SUMO_X = 302;
    const EVENT_SUMO_Y = 81;
    const EVENT_SUMO_Z = 309;
}