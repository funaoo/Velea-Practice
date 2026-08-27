<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Forms\CustomForm;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IUis;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ProfileCommand extends Command implements IUis
{
    private Cache $cache;

    public function __construct(private Loader $plugin)
    {
        parent::__construct("profile", "Valea - Profile Command", "/profile <ban|target> <target>");
        $this->setPermission("pocketmine.command.help");
        $this->cache = Cache::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof RPlayer) return;

        $form = new FormsManager();

        if (!isset($args[0])) {
            $form->getProfileForm($sender, $sender);
            return;
        }

        switch ($args[0]) {
            case "ban":
            case "banned":
                if (isset($args[1]) && isset($this->cache->ban[$args[1]])) {
                    $this->sendBanProfile($args[1], $sender);
                } else {
                    $sender->sendMessage(IUtils::PREFIX . "");
                }
                break;

            default:
                $form->getProfileForm($sender, $args[0]);
                break;
        }
    }

    public function sendBanProfile(string $target, RPlayer $staff): void
    {
        if (!isset($this->cache->ban[$target])) {
            $staff->sendMessage(IUtils::ERROR . "§6{$target} §cis not banned !");
            return;
        }

        $form = new CustomForm(function (Player $player, $data) {});
        $form->setTitle(str_replace("{player}", $target, self::PROFILE_TITLE));
        $array = Cache::getInstance()->ban[$target];
        $time = $array[FormsManager::TIME];
        $string = ($time === 0)
            ? "$target was banned for life by {$array[FormsManager::BY_NAME]} for the reason : {$array[FormsManager::REASON]}"
            : "$target will be unbanned " . date('l jS \of F Y h:i:s A', $time) . ". He had been banished by {$array[FormsManager::BY_NAME]} for reason : {$array[FormsManager::REASON]}";
        $form->addLabel($string);
        $staff->sendForm($form);
    }
}