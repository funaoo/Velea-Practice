<?php

namespace Nathan45\Valea\Entities;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Living;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\item\PotionType;
use pocketmine\utils\Color;
use pocketmine\world\sound\PotionSplashSound;
use pocketmine\world\particle\PotionSplashParticle;

class ValeaPotion extends SplashPotion
{
    protected function onHit(ProjectileHitEvent $event): void
    {
        $effects = $this->getPotionEffects();
        $hasEffects = true;

        if (empty($effects)) {
            $colors = [new Color(0x38, 0x5d, 0xc6)];
            $hasEffects = false;
        } else {
            $colors = [];
            foreach ($effects as $effect) {
                $level = $effect->getAmplifier() + 1;
                for ($j = 0; $j < $level; ++$j) {
                    $colors[] = $effect->getType()->getColor();
                }
            }
        }

        $pos = $this->getPosition();
        $world = $this->getWorld();
        $mixedColor = Color::mix(...$colors);
        $world->addParticle($pos, new PotionSplashParticle($mixedColor));
        $world->addSound($pos, new PotionSplashSound());

        if ($hasEffects) {
            if (!$this->willLinger()) {
                foreach ($world->getNearbyEntities($this->getBoundingBox()->expandedCopy(4.125, 2.125, 4.125), $this) as $entity) {
                    if ($entity instanceof Living && $entity->isAlive()) {
                        $entityPos = $entity->getPosition()->add(0, $entity->getEyeHeight(), 0);
                        $distanceSquared = $entityPos->distanceSquared($pos);

                        if ($distanceSquared > 16) continue;

                        $distanceMultiplier = 1.45 - (sqrt($distanceSquared) / 4);

                        if ($event instanceof ProjectileHitEntityEvent && $entity === $event->getEntityHit()) {
                            $distanceMultiplier = 1.0;
                        }

                        foreach ($this->getPotionEffects() as $effect) {
                            if (!$effect->getType()->isInstantEffect()) {
                                $newDuration = (int) round($effect->getDuration() * 0.75 * $distanceMultiplier);
                                if ($newDuration < 20) continue;
                                $entity->getEffects()->add($effect->setDuration($newDuration));
                            } else {
                                $effect->getType()->applyEffect($entity, $effect, $distanceMultiplier, $this, $this->getOwningEntity());
                            }
                        }
                    }
                }
            }
        } elseif ($event instanceof ProjectileHitBlockEvent && $this->getPotionType() === PotionType::WATER) {
            $blockIn = $event->getBlockHit()->getSide($event->getRayTraceResult()->getHitFace());

            if ($blockIn->getTypeId() === \pocketmine\block\BlockTypeIds::FIRE) {
                $world->setBlock($blockIn->getPosition(), VanillaBlocks::AIR());
            }

            foreach ($blockIn->getHorizontalSides() as $horizontalSide) {
                if ($horizontalSide->getTypeId() === \pocketmine\block\BlockTypeIds::FIRE) {
                    $world->setBlock($horizontalSide->getPosition(), VanillaBlocks::AIR());
                }
            }
        }
    }
}
