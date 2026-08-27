<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Forms\CustomForm;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IPermissions;
use Nathan45\Valea\Utils\Interfaces\IUis;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use Nathan45\Valea\Utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class BanCommand extends Command implements IUis
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("ban", " - Ban Command", "/ban", ["tempban"]);
        $this->setPermission("valea.ban");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer && $sender->hasPermission(IPermissions::BAN)) {
            $this->sendBanForm($sender);
        } else {
            $sender->sendMessage(IMessages::NOT_PERMISSION);
        }
    }

    public function sendBanForm(RPlayer $player): void
    {
        $reasons = [
            "Comms Abuse - Text",
            "Cheating",
            "Offensive or inappropriate Name|Skin",
            "Disrespectful Behavior",
            "Threats",
            "Other"
        ];

        $players = [];
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
            $players[] = $p->getName();
        }

        $form = new CustomForm(function (Player $player, $data) use ($reasons, $players) {
            if ($data === null || !$player instanceof RPlayer) return;

            $target = (empty($data[1])) ? $players[$data[0]] : $data[1];

            if (!isset(Cache::getInstance()->players[$target])) {
                $player->sendMessage(IUtils::ERROR . "§cplayer not found, please try again");
                return;
            }

            if (strlen($data[2]) > 255) {
                $player->sendMessage("§cThe reason given contains too many characters, try again");
                return;
            }

            (new Utils())->addBan($target, $player, $data[3] * 86400, $reasons[$data[2]]);
        });

        $form->setTitle(self::BAN_TITLE);
        $form->addDropdown("Select a player", $players);
        $form->addInput("Or enter her name");
        $form->addDropdown("Choose the reason", $reasons);
        $form->addStepSlider("Choose the time of the ban (in days)\n 0 = permanent ban", ["0", "1", "3", "7", "15", "30", "90"]);
        $player->sendForm($form);
    }
}