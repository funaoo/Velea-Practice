<?php

namespace Nathan45\Valea\Listener;

use Nathan45\Valea\Entities\Bots\Bot;
use Nathan45\Valea\Items\ValeaPearl;
use Nathan45\Valea\Listener\PracticeEvents\BoxingTapEvent;
use Nathan45\Valea\Loader;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Cache;
use Nathan45\Valea\Utils\Interfaces\IMessages;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;

class EntityListener implements Listener
{
    private Loader $plugin;

    public function __construct(Loader $main)
    {
        $this->plugin = $main;
    }

    public function onLaunch(ProjectileLaunchEvent $event): void
    {
        $cache  = Cache::getInstance();
        $entity = $event->getEntity();
        $player = $entity->getOwningEntity();

        if ($entity->getWorld()->getFolderName() === IUtils::LOBBY_WORLD_NAME) {
            $event->cancel();
        }

        if (!$player instanceof RPlayer) return;

        if ($entity instanceof ValeaPearl) {
            if (isset($cache->pearls[$player->getName()]) && $cache->pearls[$player->getName()] > time()) {
                $event->cancel();
                $player->sendPopup(str_replace(["{time}"], [$cache->pearls[$player->getName()] - time()], IMessages::PEARL_COOLDOWN));
            } else {
                $cache->pearls[$player->getName()] = time() + IUtils::PEARL_COOLDOWN;
            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Bot && (
                $event->getCause() === EntityDamageEvent::CAUSE_FALL ||
                $event->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION
            )) {
            $event->cancel();
        }

        if ($event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0) {
            $event->cancel();
        }
    }

    public function onTap(EntityDamageByEntityEvent $event): void
    {
        $entity  = $event->getEntity();
        $damager = $event->getDamager();

        if ($damager->getWorld()->getFolderName() === IUtils::LOBBY_WORLD_NAME) {
            $event->cancel();
            return;
        }

        if ($entity instanceof RPlayer && $damager instanceof RPlayer) {
            $worldName = $entity->getWorld()->getFolderName();

            match ($worldName) {
                IUtils::COMBO_FFA_WORLD_NAME => $event->setAttackCooldown(1),
                default                      => $event->setAttackCooldown(10),
            };

            if ($entity->isOnCombat() && $entity->getFighter() !== $damager) {
                $event->cancel();
                $damager->sendMessage(str_replace(["{player}", "{seconds}"], [$entity->getName(), $entity->getCombatTime()], IMessages::PLAYER_IN_COMBAT));
                return;
            }

            if ($damager->isOnCombat() && $damager->getFighter() !== $entity) {
                $event->cancel();
                $damager->sendMessage(str_replace(["{seconds}"], [$damager->getCombatTime()], IMessages::IN_COMBAT));
                return;
            }

            if ($worldName === IUtils::BOXING_FFA_WORLD_NAME) {
                $ev = new BoxingTapEvent($entity, $damager);
                $ev->call();
                $event->setBaseDamage(0);
                return;
            }

            if (!$event->isCancelled()) {
                $entity->setInCombat($damager);
                $damager->setInCombat($entity);
                $entity->getScoreboard();
            }
        }
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();

        if (!$player instanceof RPlayer) return;

        match ($event->getCause()) {
            EntityDamageEvent::CAUSE_VOID => (function () use ($event, $player) {
                $event->cancel();
                Loader::getInstance()->getServer()->dispatchCommand($player, "spawn");
            })(),
            EntityDamageEvent::CAUSE_FALL => $event->cancel(),
            default                       => null,
        };

        if (!$event->isCancelled()) {
            $remaining = (int) ceil($player->getHealth() - $event->getFinalDamage());
            $remaining = max(0, $remaining);
            $hp = $remaining . "§c❤";
            $player->setNameTag($player->getRank()->toString() . $player->getName() . "
§c" . $hp);
        }
    }

    public function onItemPickup(EntityItemPickupEvent $event): void
    {
        if ($event->getEntity() instanceof RPlayer) {
            $event->cancel();
        }
    }
}