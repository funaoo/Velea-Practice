<?php

namespace Nathan45\Valea\Utils;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;

class Inventories
{
    const VANISH_INVENTORY = -1;
    const INVENTORY_LOBBY = 0;
    const INVENTORY_NODEBUFF = 1;
    const INVENTORY_GAPPLE = 2;
    const INVENTORY_SUMO = 3;
    const INVENTORY_FIST = 4;
    const INVENTORY_RUSH = 5;
    const INVENTORY_SOUP = 6;
    const INVENTORY_BOXING = 7;
    const INVENTORY_BU = 8;
    const INVENTORY_COMBO = 9;

    const FFA = "§6FFA";
    const DUELS = "§6Duels";
    const PROFILE = "§6Profile";
    const SOCIAL = "§6Social";
    const STATS = "§6Statistics";
    const SETTINGS = "§6Settings";
    const EVENTS = "§6Events";
    const SPECTATE = "§6Spectate";
    const INVENTORIES = "§6Inventories";
    const BAN = "§6Ban";
    const FREEZE = "§6Freeze";

    public array $inventories;

    public function __construct()
    {
        $rush_helmet = VanillaItems::IRON_HELMET();
        $rush_chestplate = VanillaItems::DIAMOND_CHESTPLATE();
        $rush_leggings = VanillaItems::GOLDEN_LEGGINGS();
        $rush_boots = VanillaItems::IRON_BOOTS();
        foreach ([$rush_boots, $rush_chestplate, $rush_helmet, $rush_leggings] as $rush) {
            $rush->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION()));
        }

        $iron_sword = VanillaItems::IRON_SWORD();
        $iron_sword->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));

        $diamond_sword = VanillaItems::DIAMOND_SWORD();
        $diamond_chestplate = VanillaItems::DIAMOND_CHESTPLATE();
        $diamond_leggings = VanillaItems::DIAMOND_LEGGINGS();
        $diamond_helmet = VanillaItems::DIAMOND_HELMET();
        $diamond_boots = VanillaItems::DIAMOND_BOOTS();
        foreach ([$diamond_boots, $diamond_chestplate, $diamond_helmet, $diamond_leggings, $diamond_sword] as $util) {
            $util->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
        }

        $this->inventories = [
            self::VANISH_INVENTORY => [
                [
                    3 => VanillaBlocks::PACKED_ICE()->asItem()->setCustomName(self::FREEZE),
                    4 => (StringToItemParser::getInstance()->parse("ender_eye") ?? VanillaItems::ENDER_PEARL())->setCustomName(self::SPECTATE),
                    5 => VanillaItems::BLAZE_ROD()->setCustomName(self::BAN),
                ],
                []
            ],

            self::INVENTORY_LOBBY => [
                [
                    0 => VanillaItems::IRON_SWORD()->setCustomName(self::FFA),
                    1 => VanillaItems::GOLDEN_SWORD()->setCustomName(self::DUELS),
                    2 => VanillaItems::DIAMOND_SWORD()->setCustomName(self::EVENTS),
                    6 => VanillaBlocks::CHEST()->asItem()->setCustomName(self::INVENTORIES),
                    8 => VanillaItems::CLOCK()->setCustomName(self::SETTINGS),
                    7 => VanillaItems::NAME_TAG()->setCustomName(self::PROFILE),
                ],
                []
            ],

            self::INVENTORY_NODEBUFF => [
                0 => [0 => $diamond_sword, 1 => VanillaItems::ENDER_PEARL()->setCount(16)],
                1 => [$diamond_helmet, $diamond_chestplate, $diamond_leggings, $diamond_boots]
            ],

            self::INVENTORY_GAPPLE => [
                0 => [0 => $diamond_sword, 1 => VanillaItems::GOLDEN_APPLE()->setCount(5)],
                1 => [$diamond_helmet, $diamond_chestplate, $diamond_leggings, $diamond_boots],
            ],

            self::INVENTORY_SUMO => [
                [],
                [],
            ],

            self::INVENTORY_FIST => [
                0 => [VanillaItems::STEAK()->setCount(64)],
                1 => []
            ],

            self::INVENTORY_RUSH => [
                0 => [$iron_sword, VanillaItems::GOLDEN_APPLE()->setCount(4), VanillaItems::ENDER_PEARL(), VanillaBlocks::SANDSTONE()->asItem()->setCount(64), VanillaBlocks::SANDSTONE()->asItem()->setCount(64), VanillaBlocks::SANDSTONE()->asItem()->setCount(64), VanillaItems::IRON_PICKAXE()],
                1 => [$rush_helmet, $rush_chestplate, $rush_leggings, $rush_boots],
            ],

            self::INVENTORY_SOUP => [
                0 => [0 => $diamond_sword],
                1 => [$diamond_helmet, $diamond_chestplate, $diamond_leggings, $diamond_boots]
            ],

            self::INVENTORY_BOXING => [
                0 => [VanillaItems::DIAMOND_SWORD()],
                1 => [],
            ],

            self::INVENTORY_BU => [
                0 => [$diamond_sword, VanillaItems::BOW(), VanillaItems::FISHING_ROD(), VanillaItems::GOLDEN_APPLE()->setCount(10), VanillaItems::LAVA_BUCKET(), VanillaItems::LAVA_BUCKET(), VanillaItems::WATER_BUCKET(), VanillaItems::WATER_BUCKET(), VanillaItems::DIAMOND_PICKAXE(), VanillaBlocks::COBBLESTONE()->asItem()->setCount(64), VanillaBlocks::COBBLESTONE()->asItem()->setCount(64), VanillaBlocks::COBBLESTONE()->asItem()->setCount(64), VanillaItems::ARROW()->setCount(64)],
                1 => [$diamond_helmet, $diamond_chestplate, $diamond_leggings, $diamond_boots],
            ],

            self::INVENTORY_COMBO => [
                0 => [$diamond_sword, VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(64)],
                1 => [$diamond_helmet, $diamond_chestplate, $diamond_leggings, $diamond_boots],
            ],
        ];

        for ($i = 2; $i < 37; $i++) {
            $this->inventories[self::INVENTORY_NODEBUFF][0][$i] = VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING);
        }

        for ($i = 1; $i < 37; $i++) {
            $this->inventories[self::INVENTORY_SOUP][0][$i] = VanillaItems::MUSHROOM_STEW();
        }
    }

    public function getBaseInventories(): array
    {
        $array = [];
        for ($i = 0; $i < 10; $i++) {
            $array[] = $this->getInventory($i);
        }
        return $array;
    }

    public function getInventory($id): array
    {
        return $this->inventories[$id][0];
    }

    public function getArmorInventory($id): array
    {
        return $this->inventories[$id][1];
    }
}
