<?php

namespace Nathan45\Valea\Tasks\Delayed;

use Nathan45\Valea\Loader;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\Task;

class SandstoneDelayedTask extends Task
{
    private Block $block;
    private int $cooldown;

    public function __construct(Block $block, int $cooldown)
    {
        $this->block    = $block;
        $this->cooldown = $cooldown;
    }

    public function onRun(): void
    {
        if ($this->cooldown > 0) {
            $this->cooldown--;
        } else {
            $this->block->getPosition()->getWorld()->setBlock(
                $this->block->getPosition(),
                VanillaBlocks::AIR()
            );
            $this->getHandler()->cancel();
        }
    }
}
