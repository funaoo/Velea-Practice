<?php

namespace Nathan45\Valea\Entities;

use Nathan45\Valea\Items\FishingRod;
use Nathan45\Valea\RPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\Random;

class Hook extends Projectile
{
    public function __construct(Location $location, ?Entity $owner = null, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $owner, $nbt);

        if ($owner instanceof RPlayer) {
            $pos = $owner->getPosition();
            $this->setPosition($pos->add(0, $owner->getEyeHeight() - 1.5, 0));
            $motion = $owner->getDirectionVector()->multiply(0.3);
            $this->setMotion($motion);
            $owner->setFishingHook($this);
            $this->handleHookCasting($motion->x, $motion->y, $motion->z, 1.0, 1.0);
        }
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.2, 0.2);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.01;
    }

    protected function getInitialGravity(): float
    {
        return 0.1;
    }

    public static function getNetworkTypeId(): string
    {
        return "minecraft:fishinghook";
    }

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $event = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
        $event->call();
        $damage = $this->getResultDamage();

        if ($this->getOwningEntity() !== null) {
            $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            $entityHit->attack($ev);
        }

        $this->isCollided = true;
        $this->flagForDespawn();
    }

    public function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2): void
    {
        $rand = new Random();
        $f = sqrt($x * $x + $y * $y + $z * $z);
        if ($f == 0) return;

        $x = $x / $f;
        $y = $y / $f;
        $z = $z / $f;
        $x += $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $y += $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $z += $rand->nextSignedFloat() * 0.007499999832361937 * $f2;
        $x *= $f1;
        $y *= $f1;
        $z *= $f1;

        $current = $this->getMotion();
        $this->setMotion(new Vector3($current->x + $x, $current->y + $y, $current->z + $z));
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $owner = $this->getOwningEntity();

        if ($owner instanceof RPlayer) {
            if (!$owner->getInventory()->getItemInHand() instanceof FishingRod || !$owner->isAlive() || $owner->isClosed()) {
                $this->flagForDespawn();
            }
        } else {
            $this->flagForDespawn();
        }

        return $hasUpdate;
    }

    protected function onDispose(): void
    {
        $owner = $this->getOwningEntity();
        if ($owner instanceof RPlayer) {
            $owner->setFishingHook(null);
        }
        parent::onDispose();
    }

    public function applyGravity(): void
    {
        if ($this->isUnderwater()) {
            $motion = $this->getMotion();
            $this->setMotion(new Vector3($motion->x, $motion->y + $this->getGravity(), $motion->z));
        } else {
            parent::applyGravity();
        }
    }
}