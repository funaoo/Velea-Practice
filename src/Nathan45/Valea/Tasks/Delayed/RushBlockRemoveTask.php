<?php

namespace Nathan45\Valea\Tasks\Delayed;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class RushBlockRemoveTask extends Task
{
    public function __construct(
        private World $world,
        private Vector3 $position,
        private int $ticksLeft = 160  
    ) {}

    public function onRun(): void
    {
        $this->ticksLeft--;

        if ($this->ticksLeft <= 0) {
            if ($this->world->isLoaded()) {
                $block = $this->world->getBlockAt(
                    (int) $this->position->x,
                    (int) $this->position->y,
                    (int) $this->position->z
                );
                
                if ($block->getTypeId() !== VanillaBlocks::AIR()->getTypeId()) {
                    $this->world->setBlock($this->position, VanillaBlocks::AIR());
                }
            }
            $this->getHandler()?->cancel();
        }
    }
}
