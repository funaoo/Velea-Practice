<?php

namespace Nathan45\Valea\Items;

use Nathan45\Valea\Entities\Hook;
use Nathan45\Valea\RPlayer;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Location;
use pocketmine\item\Durable;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class FishingRod extends Durable
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::FISHING_ROD), "Fishing Rod");
    }

    public function getMaxStackSize(): int
    {
        return 1;
    }

    public function getCooldownTicks(): int
    {
        return 5;
    }

    public function getMaxDurability(): int
    {
        return 355;
    }

    public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult
    {
        if (!($player instanceof RPlayer)) return ItemUseResult::NONE;

        if (!$player->hasItemCooldown($this)) {
            $player->resetItemCooldown($this);

            if ($player->getFishingHook() === null) {
                $motion   = $player->getDirectionVector()->multiply(0.4);
                $location = Location::fromObject(
                    $player->getEyePos(),
                    $player->getWorld(),
                    $player->getLocation()->yaw,
                    $player->getLocation()->pitch
                );

                $hook = new Hook($location, $player, new CompoundTag());
                $hook->spawnToAll();
                $player->setFishingHook($hook);
            } else {
                $player->getFishingHook()->flagForDespawn();
                $player->setFishingHook(null);
            }

            $player->broadcastAnimation(new ArmSwingAnimation($player));
            return ItemUseResult::SUCCESS;
        }

        return ItemUseResult::NONE;
    }

    public function getThrowForce(): float
    {
        return 0.9;
    }
}
