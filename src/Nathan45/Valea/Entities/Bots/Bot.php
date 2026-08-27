<?php

namespace Nathan45\Valea\Entities\Bots;

use Nathan45\Valea\Listener\PracticeEvents\BotDuelEndEvent;
use Nathan45\Valea\RPlayer;
use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Attribute;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;

abstract class Bot extends Human
{
    const EASY = 1;
    const MEDIUM = 2;
    const HARD = 3;
    const HACKER = 4;
    const CUSTOM = 5;

    public int $jumpTicks = 5;
    public int $hitTicks = 0;
    public float $speed = 0.4;
    public int $freeze = 0;

    protected ?RPlayer $target = null;
    public ?string $botName = null;
    public int $difficulty = 1;
    public array $options = [];

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null, ?RPlayer $target = null, ?string $botName = null, int $difficulty = 1, array $options = [])
    {
        parent::__construct($location, $skin, $nbt);
        $this->target = $target;
        $this->botName = $botName;
        $this->difficulty = $difficulty;
        $this->options = $options;
        $this->setCanSaveWithChunk(false);
        $this->freeze = time() + 11;
    }

    public function getTarget(): ?RPlayer
    {
        return $this->target;
    }

    public function setTarget(?RPlayer $target): void
    {
        $this->target = $target;
    }

    abstract public function setInventory(): void;

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->freeze >= time()) return true;

        parent::entityBaseTick($tickDiff);

        $target = $this->getTarget();
        if (!$target instanceof RPlayer || !$this->isAlive() || !$target->isAlive()) {
            if (!$this->isClosed()) $this->flagForDespawn();
            return false;
        }

        $this->setNameTag(($this->botName === null) ? "§cValea §fBot" : $this->botName . "\n§7[§b" . $this->getHealth() * 5 . "§7]");

        $pos = $target->getPosition();
        $myPos = $this->getPosition();
        $x = $pos->x - $myPos->x;
        $z = $pos->z - $myPos->z;

        if ($x != 0 || $z != 0) {
            $tot = abs($x) + abs($z);
            if ($tot != 0) {
                $motion = $this->getMotion();
                $motion->x = $this->speed * 0.35 * ($x / $tot);
                $motion->z = $this->speed * 0.35 * ($z / $tot);
                $this->setMotion($motion);
            }
        }

        if ($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }

        if ($this->shouldJump()) {
            $this->jump();
        }

        return $this->isAlive();
    }

    public function getMaxHealth(): int
    {
        return match ($this->getDifficulty()) {
            self::CUSTOM => $this->options["health"],
            self::EASY   => 10,
            self::MEDIUM => 15,
            self::HARD   => 20,
            self::HACKER => 25,
            default      => 20,
        };
    }

    public function getReach(): int
    {
        return match ($this->getDifficulty()) {
            self::CUSTOM => $this->options["reach"],
            self::HACKER => 4,
            self::EASY   => 1,
            self::MEDIUM => 2,
            default      => 3,
        };
    }

    public function getAccuracy(): int
    {
        return match ($this->getDifficulty()) {
            self::CUSTOM => $this->options["accuracy"],
            self::HACKER => 80,
            self::EASY   => 10,
            self::MEDIUM => 20,
            default      => 25,
        };
    }

    public function getDamage(): int
    {
        return match ($this->getDifficulty()) {
            self::CUSTOM => $this->options["damage"],
            self::HACKER => 10,
            self::EASY   => 4,
            self::MEDIUM => 6,
            self::HARD   => 8,
            default      => 6,
        };
    }

    public function getItemIndex(): int
    {
        return 0;
    }

    public function getJumpMultiplier(): int
    {
        return 2;
    }

    public function shouldJump(): bool
    {
        if ($this->jumpTicks > 0) return false;
        $pos = $this->getPosition();
        $frontBlock = $this->getFrontBlock();
        $frontBlockNeg = $this->getFrontBlock(-1);
        $belowBlock = $this->getWorld()->getBlock($pos->add(0, -0.5, 0));
        return $this->isCollidedHorizontally
            || (!($frontBlock->getTypeId() === \pocketmine\block\BlockTypeIds::AIR) || $frontBlockNeg instanceof Stair)
            || (($belowBlock instanceof Slab && (!($this->getFrontBlock(-0.5) instanceof Slab) && $this->getFrontBlock(-0.5)->getTypeId() !== \pocketmine\block\BlockTypeIds::AIR))
                && $this->getFrontBlock(1)->getTypeId() === \pocketmine\block\BlockTypeIds::AIR
                && $this->getFrontBlock(2)->getTypeId() === \pocketmine\block\BlockTypeIds::AIR
                && !$frontBlock instanceof Flowable
                && $this->jumpTicks === 0);
    }

    public function getFrontBlock(float $y = 0): Block
    {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->floor();
        return $this->getWorld()->getBlockAt((int) $pos->x, (int) $pos->y, (int) $pos->z);
    }

    public function getDifficulty(): int
    {
        return $this->difficulty;
    }

    public function jump(): void
    {
        $motion = $this->getMotion();
        $motion->y = $this->getGravity() * $this->getJumpMultiplier();
        $this->move($motion->x * 1.25, $motion->y, $motion->z * 1.25);
        $this->jumpTicks = 5;
    }

    public function attackTarget(): void
    {
        if (mt_rand(1, (100 / $this->getAccuracy())) === 1) {
            $this->lookAt($this->getTarget()->getPosition());
        }
        if ($this->jumpTicks > 0) $this->jumpTicks--;
    }

    public function isLookingAt(Vector3 $target): bool
    {
        $pos = $this->getPosition();
        $horizontal = sqrt(($target->x - $pos->x) ** 2 + ($target->z - $pos->z) ** 2);
        $vertical = $target->y - $pos->y;
        $expectedPitch = -atan2($vertical, $horizontal) / M_PI * 180;

        $xDist = $target->x - $pos->x;
        $zDist = $target->z - $pos->z;
        $expectedYaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if ($expectedYaw < 0) {
            $expectedYaw += 360.0;
        }

        return abs($expectedPitch - $this->getLocation()->pitch) <= 5 && abs($expectedYaw - $this->getLocation()->yaw) <= 10;
    }

    public function knockBack(float $x, float $z, float $force = self::DEFAULT_KNOCKBACK_FORCE, ?float $verticalLimit = self::DEFAULT_KNOCKBACK_VERTICAL_LIMIT): void
    {
        $f = sqrt($x * $x + $z * $z);
        if ($f <= 0) return;

        if (mt_rand() / mt_getrandmax() > $this->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)->getValue()) {
            $f = 1 / $f;

            $kbx = IUtils::KB_X;
            $kby = IUtils::KB_Y;
            $kbz = IUtils::KB_Z;

            $motion = clone $this->getMotion();
            $motion->x /= 2;
            $motion->y /= 2;
            $motion->z /= 2;
            $motion->x += $x * $f * $kbx;
            $motion->y += $kby;
            $motion->z += $z * $f * $kbz;

            if ($motion->y > $kby) {
                $motion->y = $kby;
            }

            $this->setMotion($motion);
        }

        $this->hitTicks = Server::getInstance()->getTick();
    }

    public function attack(EntityDamageEvent $source): void
    {
        parent::attack($source);
        $this->hitTicks = Server::getInstance()->getTick();
    }

    protected function onDeath(): void
    {
        parent::onDeath();
        (new BotDuelEndEvent($this->target, $this, null))->call();
    }

    public function recentlyHit(): bool
    {
        return Server::getInstance()->getTick() - $this->hitTicks <= 4;
    }
}