<?php

namespace Nathan45\Valea\Items;

use Nathan45\Valea\Utils\Interfaces\IUtils;
use pocketmine\item\EnderPearl;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;

class ValeaPearl extends EnderPearl
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::ENDER_PEARL), "Ender Pearl");
    }

    public function getThrowForce(): float
    {
        return 2.0;
    }

    public function getCooldownTicks(): int
    {
        return 20 * IUtils::PEARL_COOLDOWN;
    }
}
