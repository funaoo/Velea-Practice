<?php

namespace Nathan45\Valea\Items;

use Nathan45\Valea\RPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\RawSalmon;
use pocketmine\item\VanillaItems;

class GoldenHead extends RawSalmon
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::RAW_SALMON), "Golden Head");
    }

    public function requiresHunger(): bool
    {
        return false;
    }

    public function onConsume(Living $consumer): void
    {
        parent::onConsume($consumer);
    }

    public function getResidue(): Item
    {
        return VanillaItems::GOLDEN_APPLE();
    }

    public function getAdditionalEffects(): array
    {
        return [
            new EffectInstance(VanillaEffects::REGENERATION(), 100, 1),
            new EffectInstance(VanillaEffects::ABSORPTION(), 2400 * 2),
        ];
    }
}