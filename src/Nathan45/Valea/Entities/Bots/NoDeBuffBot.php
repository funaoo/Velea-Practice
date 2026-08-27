<?php

namespace Nathan45\Valea\Entities\Bots;

use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Inventories;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Sword;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;

class NoDeBuffBot extends Bot
{
    const NETWORK_ID = "NoDeBuffBot";

    const POT_COOLDOWN = 5;
    const AGRO_COOLDOWN = 175;
    const POT_COUNT = 34;

    private int $pearlsRemaining = 16;
    private int $neededPots = 0;
    private int $potTicks = 0;
    private int $agroTicks = 0;
    private int $potionsRemaining = self::POT_COUNT;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null, ?RPlayer $target = null, ?string $botName = null, int $difficulty = 0, array $options = [])
    {
        parent::__construct($location, $skin, $nbt, $target, $botName, $difficulty, $options);
        if ($target instanceof RPlayer) {
            $this->setSkin($target->getSkin());
        }
    }

    public function setInventory(): void
    {
        $this->getInventory()->setContents((new Inventories())->getInventory(Inventories::INVENTORY_NODEBUFF));
        $this->getArmorInventory()->setContents((new Inventories())->getArmorInventory(Inventories::INVENTORY_NODEBUFF));
        $this->getInventory()->setHeldItemIndex(0);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);
        $target = $this->getTarget();

        if ($this->getHealth() < round($this->getMaxHealth() / 4)) {
            if ($this->potionsRemaining > 0) {
                $this->pot();
            } else {
                if (!$this->recentlyHit()) {
                    $motion = $this->getMotion();
                    $this->move($motion->x, $motion->y, $motion->z);
                    if ($this->shouldJump()) $this->jump();
                }
                $this->attackTarget();
            }
        } else {
            if ($this->distance($target) > $this->getReach() || !$this->recentlyHit()) {
                $motion = $this->getMotion();
                $this->move($motion->x, $motion->y, $motion->z);
                if ($this->shouldJump()) $this->jump();
            }
            if ($this->shouldJump()) $this->jump();

            if ($this->neededPots === 1) {
                if ($this->potionsRemaining > 0) {
                    $this->pot();
                } else {
                    $this->attackTarget();
                }
            } else {
                $this->attackTarget();
            }
        }

        if (Server::getInstance()->getTick() - $this->hitTicks >= 60) {
            if ($this->getHealth() <= round($this->getMaxHealth() / 2)) {
                $this->pot();
            }
        }

        if ($this->distance($target) > 20) {
            $this->pearl();
        }

        if ($this->distance($target) > 0.25 && $this->distance($target) < 4 && $target->getHealth() <= round((($this->getMaxHealth() / 4) * 3)) && $this->canAgroPearl()) {
            $this->pearl(true);
        }

        return $this->isAlive();
    }

    public function pearl(bool $agro = false): void
    {
        if ($this->pearlsRemaining > 0) {
            $max = $agro ? 2 : 5;
            if ($agro) $this->agroTicks = Server::getInstance()->getTick();
            --$this->pearlsRemaining;
            $this->teleport($this->getTarget()->getPosition()->subtract(mt_rand(0, $max), 0, mt_rand(0, $max)));
        }
    }

    public function attackTarget(): void
    {
        parent::attackTarget();
        if ($this->isLookingAt($this->getTarget()->getPosition())) {
            if ($this->distance($this->getTarget()->getPosition()) <= $this->getReach()) {
                $this->getInventory()->setHeldItemIndex(0);
                if (Server::getInstance()->getTick() - $this->potTicks >= self::POT_COOLDOWN) {
                    $event = new EntityDamageByEntityEvent(
                        $this,
                        $this->getTarget(),
                        EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK,
                        $this->getInventory()->getItemInHand() instanceof Sword ? $this->getDamage() : 0.5
                    );
                    $this->broadcastAnimation(new \pocketmine\entity\animation\ArmSwingAnimation($this));
                    if ($this->shouldJump()) $this->jump();
                    $this->getTarget()->attack($event);
                }
            }
        }
    }

    public function pot(): void
    {
        $yaw = $this->getLocation()->yaw;
        if ($yaw < 0) {
            $yaw = abs($yaw);
        } elseif ($yaw == 0) {
            $yaw = -180;
        } else {
            $yaw = -$yaw;
        }

        $this->setRotation($yaw, 85);
        $this->getInventory()->setHeldItemIndex(2);
        ++$this->neededPots;
        --$this->potionsRemaining;
        $this->potTicks = Server::getInstance()->getTick();
    }

    public function canAgroPearl(): bool
    {
        return $this->agroTicks === 0 || Server::getInstance()->getTick() - $this->agroTicks >= self::AGRO_COOLDOWN;
    }
}