<?php

namespace Nathan45\Valea\Entities;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;

class FloatingTextEntity extends Human
{
    protected float $gravity = 0.0;
    protected bool $gravityEnabled = false;

    private string $floatingTextId = "";

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $skin, $nbt);
        $this->setNoClientPredictions(true);
    }

    public function initFloatingText(): void
    {
        $this->floatingTextId = uniqid("ft_", true);
        $this->setNameTagAlwaysVisible(true);
    }

    public function getFloatingTextId(): string
    {
        return $this->floatingTextId;
    }
}
