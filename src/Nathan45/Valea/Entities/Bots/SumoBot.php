<?php

namespace Nathan45\Valea\Entities\Bots;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Inventories;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Sword;
use pocketmine\nbt\tag\CompoundTag;

class SumoBot extends Bot
{
    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null, ?RPlayer $target = null, ?string $botName = null, int $difficulty = 1, array $options = [])
    {
        parent::__construct($location, $skin, $nbt, $target, $botName, $difficulty, $options);
    }

    public function setInventory(): void
    {
        $this->getInventory()->setContents([]);
        $this->getArmorInventory()->setContents((new Inventories())->getArmorInventory(Inventories::INVENTORY_NODEBUFF));
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);

        if ($this->shouldJump()) $this->jump();

        $target = $this->getTarget();
        if ($this->distance($target) > 3) {
            $motion = $this->getMotion();
            $this->move($motion->x, $motion->y, $motion->z);
        } else {
            $this->attackTarget();
        }

        return $this->isAlive();
    }

    public function attackTarget(): void
    {
        parent::attackTarget();
        if ($this->isLookingAt($this->getTarget()->getPosition())) {
            if ($this->distance($this->getTarget()->getPosition()) <= $this->getReach()) {
                $event = new EntityDamageByEntityEvent(
                    $this,
                    $this->getTarget(),
                    EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK,
                    $this->getInventory()->getItemInHand() instanceof Sword ? $this->getDamage() : 0.5
                );
                $this->broadcastAnimation(new ArmSwingAnimation($this));
                if ($this->shouldJump()) $this->jump();
                $this->getTarget()->attack($event);
            }
        }
    }
}