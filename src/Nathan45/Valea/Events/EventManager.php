<?php

namespace Nathan45\Valea\Events;

use Nathan45\Valea\Listener\PracticeEvents\EventStartEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\world\World;

class EventManager
{
    const EVENT_UNKNOWN  = 0;
    const EVENT_GAPPLE   = 1;
    const EVENT_NODEBUFF = 2;
    const EVENT_SUMO     = 3;

    private Loader $plugin;
    private Cache $cache;

    public function __construct()
    {
        $this->plugin = Loader::getInstance();
        $this->cache  = Cache::getInstance();
    }

    public function registerEvent(RPlayer $hoster, string $type, bool $private, string|null $password): void
    {
        $event = new Event($hoster, $type, $private, $password);
        $this->cache->events[$hoster->getName()] = $event;

        $ev = new EventStartEvent($event);
        $ev->call();

        if (!$ev->isCancelled()) {
            $this->cache->events[$hoster->getName()] = $event;
            $this->plugin->getServer()->broadcastMessage(IUtils::PREFIX . "§aA new {$type} event has started by {$hoster->getName()}! Run /event list or /event join");
        }
    }

    public function getEvent(string $eventName): ?Event
    {
        return $this->cache->events[$eventName] ?? null;
    }

    public function getEvents(): array
    {
        return $this->cache->events;
    }

    public function getLevelForEvent(string $type): ?World
    {
        $levelName = match (strtolower($type)) {
            "gapple"   => IUtils::EVENT_GAPPLE,
            "nodebuff" => IUtils::EVENT_NODEBUFF,
            "sumo"     => IUtils::EVENT_SUMO,
            default    => null
        };

        if ($levelName === null) return null;

        $worldManager = $this->plugin->getServer()->getWorldManager();
        $worldManager->loadWorld($levelName);
        return $worldManager->getWorldByName($levelName);
    }
}