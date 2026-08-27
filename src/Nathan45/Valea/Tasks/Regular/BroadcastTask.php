<?php

namespace Nathan45\Valea\Tasks\Regular;

class BroadcastTask extends PracticeTask
{
    private array $messages = [
        "§7[§3VALEA§7] §rJoin our discord - discord.gg/valea",
        "§7[§3VALEA§7] §rSee a player breaking the rules? do /report",
        "§7[§3VALEA§7] §rThank you all for playing!",
        "§7[§3VALEA§7] §rRequire assistance? join our discord - discord.gg/valea",
        "§7[§3VALEA§7] §rCheck our our store - valea.tebex.io",
        "§7[§3VALEA§7] §rFound a bug? report it do /report",
        "§7[§3VALEA§7] §rIf you would like to apply for staff join our discord - discord.gg/valea",
        "§7[§3VALEA§7] §rWant a rank with many cool perks? look at our store - valea.tebex.io",
        "§7[§3VALEA§7] §rPlease keep the chat friendly and enjoy playing!",
        "§7[§3VALEA§7] §rPlease follow the rules, do /rules!",
        "§7[§3VALEA§7] §rJoin our discord to have a chance to win giveaways - discord.gg/valea",
        "§7[§3VALEA§7] §rPlease vote for us - vote.valeanetwork.eu",
        "§7[§3VALEA§7] §rIf you are a content creator apply for media rank - discord.gg/valea",
    ];

    private int $old = 0;

    public function __construct()
    {
        parent::__construct(self::TASK_BROADCAST);
    }

    public function run(): void
    {
        $this->plugin->getServer()->broadcastMessage($this->messages[$this->old]);
        ++$this->old;
        if ($this->old > count($this->messages) - 1) $this->old = 0;
    }

    public function end(): void
    {
    }

    public function getPeriod(): int
    {
        return 5 * 60 * 20;
    }
}