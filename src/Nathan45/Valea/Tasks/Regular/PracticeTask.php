<?php

namespace Nathan45\Valea\Tasks\Regular;

use Nathan45\Valea\Loader;
use Nathan45\Valea\Utils\Cache;
use pocketmine\scheduler\Task;

abstract class PracticeTask extends Task
{
    const TASK_SCOREBOARD = 1;
    const TASK_BROADCAST  = 2;
    const TASK_CPS        = 3;

    const STATUS_STOPPED = 0;
    const STATUS_RUNNING = 1;

    private int $status = self::STATUS_STOPPED;

    public Cache $cache;
    public Loader $plugin;

    public function __construct(private int $id)
    {
        $this->cache  = Cache::getInstance();
        $this->plugin = Loader::getInstance();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status = self::STATUS_RUNNING): void
    {
        $this->status = $status;
        if ($status === self::STATUS_STOPPED) $this->end();
    }

    abstract public function getPeriod(): int;

    public function isRunning(): bool
    {
        return $this->getStatus() === self::STATUS_RUNNING;
    }

    final public function onRun(): void
    {
        if ($this->isRunning()) $this->run();
    }

    abstract public function run(): void;

    abstract public function end(): void;
}