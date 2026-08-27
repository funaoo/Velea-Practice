<?php

namespace Nathan45\Valea\Scoreboards;

use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Events\Event;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat as TE;

class Scoreboard implements IUtils
{
    const DISPLAYS_NAME = "§6Valea §fNetwork";

    const SCOREBOARD_LOBBY           = 0;
    const SCOREBOARD_FFA             = 1;
    const SCOREBOARD_DUEL            = 2;
    const SCOREBOARD_BOXING          = 3;
    const SCOREBOARD_EVENT_REMAINING = 4;
    const SCOREBOARD_EVENT_STARTED   = 5;

    const SCOREBOARDS = [
        self::SCOREBOARD_LOBBY => [
            2 => TE::WHITE . "",
            3 => TE::GOLD . " Online:",
            4 => TE::WHITE . " {online}",
            5 => "§r",
            6 => TE::GOLD . " Rank:",
            7 => TE::WHITE . " {rank}",
            8 => "§r§r",
            9 => TE::WHITE . "§r",
        ],

        self::SCOREBOARD_FFA => [
            1 => TE::WHITE . "",
            2 => "§r ",
            3 => TE::GREEN . " Your Ping: " . TE::WHITE . "{ping}",
            4 => TE::GOLD . " Their Ping: " . TE::WHITE . "{a_ping}",
            5 => TE::RED . " Combat: " . TE::WHITE . "{combat}",
            6 => "§r",
            7 => TE::WHITE . "§r",
        ],

        self::SCOREBOARD_DUEL => [
            1 => TE::WHITE . "",
            2 => "§r ",
            3 => TE::GREEN . " Your Ping: " . TE::WHITE . "{ping}",
            4 => TE::GOLD . " Their Ping: " . TE::WHITE . "{a_ping}",
            5 => TE::RED . " Combat: " . TE::WHITE . "{combat}",
            6 => "§r",
            7 => TE::WHITE . "§r",
        ],

        self::SCOREBOARD_BOXING => [
            1  => "",
            2  => "§r",
            3  => TE::GOLD . " Fighting: " . TE::WHITE . "{attacker}",
            4  => "§r§r",
            5  => TE::GOLD . " Hits:",
            6  => TE::GOLD . " You: " . TE::WHITE . "{hit}",
            7  => TE::GOLD . " Them: " . TE::WHITE . "{a_hit}",
            8  => "§r§r§r",
            9  => TE::GOLD . " Your Ping: " . TE::WHITE . "{ping}",
            10 => TE::GOLD . " Their Ping: " . TE::WHITE . "{a_ping}",
            11 => "§r§r§r§r",
            12 => "§r",
        ],

        self::SCOREBOARD_EVENT_REMAINING => [
            1 => "",
            2 => "§r",
            3 => TE::DARK_AQUA . " the event starts in " . TE::WHITE . "{sec} " . TE::DARK_AQUA . "seconds",
            4 => "§r§r",
            5 => TE::DARK_AQUA . " Hosted by " . TE::WHITE . "{hoster}",
            6 => "§r§r§r",
            7 => "§r",
        ],

        self::SCOREBOARD_EVENT_STARTED => [
            1 => "",
            2 => "§r",
            3 => TE::DARK_AQUA . " round: " . TE::WHITE . "{round}",
            4 => TE::DARK_AQUA . " Between " . TE::WHITE . "{fighter1}" . TE::DARK_AQUA . " and " . TE::WHITE . "{fighter2}",
            5 => "§r§r",
            6 => TE::DARK_AQUA . " Hosted by " . TE::WHITE . "{hoster}",
            7 => "§r§r§r",
            8 => "§r",
        ],
    ];

    private Cache $cache;
    private Loader $plugin;

    public function __construct(
        private RPlayer $player,
        private int $type,
        public Bot|RPlayer|null $target = null,
        private array|null $imposedLines = null,
        public null|Event $event = null
    ) {
        if ($this->target instanceof Bot) $this->target = null;
        $this->cache  = Cache::getInstance();
        $this->plugin = Loader::getInstance();
        if ($player->getAllowedScoreboard()) $this->update($type, self::DISPLAYS_NAME, $imposedLines);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getPlayer(): RPlayer
    {
        return $this->player;
    }

    public function remove(): void
    {
        $player = $this->getPlayer();
        if (!$player->isConnected()) return;
        $pk = RemoveObjectivePacket::create($player->getName());
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function getScoreboards(): array
    {
        return $this->cache->scoreboards;
    }

    public function getObjectiveName(): string
    {
        return $this->getPlayer()->getName();
    }

    public function setLine(int $score, string $line): void
    {
        $player = $this->getPlayer();

        $entry               = new ScorePacketEntry();
        $entry->objectiveName = $this->getObjectiveName();
        $entry->type         = ScorePacketEntry::TYPE_FAKE_PLAYER;
        $entry->customName   = str_replace(
            ["{online}", "{rank}", "{attacker}", "{ping}", "{a_ping}", "{hit}", "{a_hit}", "{combat}", "{pearl}", "{hoster}", "{round}", "{sec}", "{fighter1}", "{fighter2}"],
            [
                count($this->plugin->getServer()->getOnlinePlayers()),
                $player->getRank()->getName(),
                ($this->target === null) ? " " : $this->target->getName(),
                $player->getNetworkSession()->getPing(),
                ($this->target === null || !$this->target->isConnected()) ? " " : $this->target->getNetworkSession()->getPing(),
                $player->getHit(),
                ($this->target === null) ? 0 : $this->target->getHit(),
                ($player->getCombatTime() === 0) ? " " : $player->getCombatTime(),
                $player->getPearlCooldown(),
                ($this->event === null) ? " " : $this->event->getHoster()->getName(),
                ($this->event === null) ? " " : $this->event->getRound(),
                ($this->event === null) ? " " : $this->event->getTimeRemaining(),
                ($this->event === null || !$this->event->getFighter1() instanceof RPlayer) ? " " : $this->event->getFighter1()->getName(),
                ($this->event === null || !$this->event->getFighter2() instanceof RPlayer) ? " " : $this->event->getFighter2()->getName(),
            ],
            $line
        );
        $entry->score        = $score;
        $entry->scoreboardId = $score;

        if (!$player->isConnected()) return;
        $pk = SetScorePacket::create(SetScorePacket::TYPE_CHANGE, [$entry]);
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function getTarget(): ?RPlayer
    {
        return $this->target;
    }

    public function setTarget(?RPlayer $target): void
    {
        $this->target = $target;
    }

    public function setLines(array $lines): void
    {
        foreach ($lines as $score => $line) {
            $this->setLine($score, $line);
        }
    }

    public function getLines(int $type = 0): array
    {
        return self::SCOREBOARDS[$type];
    }

    public function update(?int $type = null, ?string $displayName = null, ?array $imposedLines = null): void
    {
        $player = $this->player;
        if (!$player->isConnected()) return;

        if ($type === null) $type = $this->getType();
        $lines = $this->getLines($type);
        if (is_array($imposedLines)) $lines = $imposedLines;
        if ($displayName === null) $displayName = self::DISPLAYS_NAME;

        if (isset($this->cache->scoreboards[$player->getName()])) {
            $this->cache->scoreboards[$player->getName()]->remove();
        }

        $pk = SetDisplayObjectivePacket::create("sidebar", $player->getName(), $displayName, "dummy", 0);
        $player->getNetworkSession()->sendDataPacket($pk);

        $this->setLines($lines);
    }
}