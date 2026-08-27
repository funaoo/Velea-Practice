<?php

namespace Nathan45\Valea\Tasks\Async;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MotdAsyncTask extends AsyncTask
{
    private static array $motd = [
        "§7[§cVALEA§7] §cRETURN!",
        "§7[§6VALEA§7] §6RETURN!",
        "§7[§dVALEA§7] §dRETURN!",
        "§7[§eVALEA§7] §eRETURN!",
        "§7[§aVALEA§7] §aRETURN!",
    ];

    private static int $old = 0;

    public function onRun(): void
    {
    }

    public function onCompletion(): void
    {
        Server::getInstance()->getNetwork()->setName(self::$motd[self::$old]);
        self::$old = (self::$old + 1) % count(self::$motd);
    }
}
