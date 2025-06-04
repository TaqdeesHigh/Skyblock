<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\MinionTypes;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use taqdees\Skyblock\entities\BaseMinion;

class WheatMinion extends BaseMinion {

    protected function getMinionColor(): string {
        return "\xFF\xFF\x00\xFF";
    }

    protected function canWorkOnBlock(Vector3 $blockPos): bool {
        $world = $this->getWorld();
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        return $block->getTypeId() === VanillaBlocks::WHEAT()->getTypeId();
    }

    protected function doWork(): void {
        if ($this->targetBlock === null) return;
        
        $world = $this->getWorld();
        $blockPos = $this->targetBlock;
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        
        if ($block->getTypeId() === VanillaBlocks::WHEAT()->getTypeId()) {
            $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::WHEAT());
            $world->dropItem($blockPos, VanillaItems::WHEAT());
            $world->dropItem($blockPos, VanillaItems::WHEAT_SEEDS());
        }
    }

    public function getSaveId(): string {
        return "wheat_minion";
    }
}