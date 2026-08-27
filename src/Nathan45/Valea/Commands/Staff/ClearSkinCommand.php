<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class ClearSkinCommand extends Command
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct('clearskin', 'Valea - Skin Command', '/clearskin');
        $this->setPermission("valea.clear.skin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer || !$sender->hasPermission(IPermissions::CLEAR_SKIN)) return;
        (new FormsManager())->sendOnlinePlayersForm($sender, FormsManager::CLEAR_SKIN);
    }
}