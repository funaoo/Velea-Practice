<?php

namespace Nathan45\Valea\Commands\Staff;

use Nathan45\Valea\Events\Event;
use Nathan45\Valea\Events\EventManager;
use Nathan45\Valea\Listener\PracticeEvents\PlayerJoinEventEvent;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Forms\CustomForm;
use Nathan45\Valea\Utils\Forms\SimpleForm;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUis;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class EventCommand extends Command
{
    public function __construct()
    {
        parent::__construct("event", "Staff - Event Command", "/event <join|create|list>");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!isset($args[0])) {
            $sender->sendMessage($this->usageMessage);
            return;
        }

        if (!$sender instanceof RPlayer) return;

        switch (strtolower($args[0])) {
            case "start":
            case "create":
                $this->sendNewEventForm($sender);
                break;

            case "join":
            case "list":
                $this->sendEventListForm($sender);
                break;

            default:
                $sender->sendMessage($this->usageMessage);
        }
    }

    private function sendNewEventForm(RPlayer $player): void
    {
        $array = ["Gapple", "Sumo", "NoDeBuff"];

        $form = new CustomForm(function (Player $player, array $data = null) use ($array) {
            if ($data === null || !$player instanceof RPlayer) return;

            if ($data[1] === true && empty($data[2])) {
                $player->sendMessage(IUtils::PREFIX . "if you want a private event, please enter a password!");
                return;
            }

            $player->sendMessage(IMessages::SUCCESSFUL);
            (new EventManager())->registerEvent($player, $array[$data[3]], $data[1], $data[2]);
        });

        $form->setTitle(IUis::NEW_EVENT_TITLE);
        $form->addLabel(IUis::NEW_EVENT_CONTENT);
        $form->addToggle("Private Event : ", false);
        $form->addInput("if it's a private event, enter a password");
        $form->addDropdown("Select the type of the event", $array);
        $player->sendForm($form);
    }

    private function sendEventListForm(RPlayer $player): void
    {
        $manager = new EventManager();

        if (empty($manager->getEvents())) {
            $player->sendMessage(IUtils::PREFIX . "§cThere are no events at the moment.");
            return;
        }

        $form = new SimpleForm(function (Player $player, $data = null) use ($manager) {
            if ($data === null || !$player instanceof RPlayer) return;

            $event = Cache::getInstance()->events[$data];

            if ($event->isPrivate()) {
                $this->sendPasswordForm($player, $event);
                return;
            }

            $ev = new PlayerJoinEventEvent($player, $event);
            $ev->call();

            if (!$ev->isCancelled()) $event->addPlayer($player);
        });

        $form->setTitle(IUis::EVENT_LIST_TITLE);
        $form->setContent(IUis::NEW_EVENT_CONTENT);

        foreach ($manager->getEvents() as $event) {
            if ($event->hasStarted()) continue;

            $msg = ($event->isPrivate()) ? "§cPrivate" : "§aPublic";
            $form->addButton(str_replace(
                [EventManager::EVENT_GAPPLE, EventManager::EVENT_NODEBUFF, EventManager::EVENT_SUMO],
                ["Gapple", "NoDeBuff", "Sumo"],
                "§cHoster: §f" . $event->getName() . "\n§cType: §f" . $event->getType() . "\n" . $msg
            ), -1, "", $event->getName());
        }

        $player->sendForm($form);
    }

    private function sendPasswordForm(RPlayer $player, Event $event, bool $rated = false): void
    {
        $form = new CustomForm(function (Player $player, $data) use ($event) {
            if ($data === null || !$player instanceof RPlayer) return;

            if ($data[0] === $event->getPassword()) {
                $ev = new PlayerJoinEventEvent($player, $event);
                $ev->call();

                if (!$ev->isCancelled()) $event->addPlayer($player);
            } else {
                $this->sendPasswordForm($player, $event, true);
            }
        });

        $form->setTitle(IUis::EVENT_PASSWORD_TITLE);
        $form->addInput($rated ? "The password is incorrect, try again" : "Enter the password");
        $player->sendForm($form);
    }
}