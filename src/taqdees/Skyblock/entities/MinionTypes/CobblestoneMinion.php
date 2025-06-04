<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\MinionTypes;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use taqdees\Skyblock\entities\BaseMinion;

class CobblestoneMinion extends BaseMinion {

    protected function getMinionColor(): string {
        return "\x7F\x7F\x7F\xFF";
    }

    protected function canWorkOnBlock(Vector3 $blockPos): bool {
        $world = $this->getWorld();
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        return $block->getTypeId() === VanillaBlocks::COBBLESTONE()->getTypeId();
    }

    protected function doWork(): void {
        if ($this->targetBlock === null) return;
        
        $world = $this->getWorld();
        $blockPos = $this->targetBlock;
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        
        if ($block->getTypeId() === VanillaBlocks::COBBLESTONE()->getTypeId()) {
            $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::AIR());
            $world->dropItem($blockPos, VanillaBlocks::COBBLESTONE()->asItem());
        }
    }

    public function getSaveId(): string {
        return "cobblestone_minion";
    }
}