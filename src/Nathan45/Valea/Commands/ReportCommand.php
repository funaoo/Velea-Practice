<?php

namespace Nathan45\Valea\Commands;

use Nathan45\Valea\Discord\Embed;
use Nathan45\Valea\Discord\Message;
use Nathan45\Valea\Discord\Webhook;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Forms\CustomForm;
use Nathan45\Valea\Utils\Forms\SimpleForm;
use Nathan45\Valea\Utils\FormsManager;
use Nathan45\Valea\Utils\Interfaces\IUis;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ReportCommand extends Command implements IUis
{
    public function __construct(private Loader $plugin)
    {
        parent::__construct("report", "Valea - Report Command", "/report");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($sender instanceof RPlayer) {
            $this->sendBasicReportForm($sender);
        }
    }

    public function sendBasicReportForm(RPlayer $player): void
    {
        $form = new SimpleForm(function (Player $player, int $data = null) {
            if ($data === null || !$player instanceof RPlayer) return;

            if ($data === 0) {
                (new FormsManager())->sendOnlinePlayersForm($player, FormsManager::REPORT);
            } else {
                $this->sendReportTextForm($player);
            }
        });
        $form->setTitle(self::BASIC_REPORT_TITLE);
        $form->setContent(self::BASIC_REPORT_CONTENT);
        $form->addButton("§7Report a player");
        $form->addButton("§7Report a(n) issue|bug");
        $player->sendForm($form);
    }

    public function sendReportTextForm(RPlayer $player): void
    {
        $form = new CustomForm(function (Player $player, array $data = null) {
            if ($data === null) return;

            $msg = new Message();
            $embed = new Embed();
            $msg->setUsername($player->getName());
            $embed->setTitle("Issue|bug reported");
            $embed->setColor(IUtils::BLUE);
            $embed->setFooter(date('l jS \of F Y h:i:s A'));
            $embed->setDescription($player->getName() . " has reported this : " . $data[1]);
            $msg->addEmbed($embed);
            (new Webhook(IUtils::REPORT_WEBHOOK))->send($msg);

            $player->sendMessage(IUtils::PREFIX . "You help us to improve this server, thank you!");
        });
        $form->setTitle(self::BASIC_REPORT_TITLE);
        $form->addLabel("You help us to improve this server!");
        $form->addInput("Explain this bug/issue as precisely as possible", "Thank you!");
        $player->sendForm($form);
    }
}