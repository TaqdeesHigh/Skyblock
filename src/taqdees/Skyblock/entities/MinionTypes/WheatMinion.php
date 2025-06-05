<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\MinionTypes;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\minions\professions\Profession;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;

class WheatMinion extends BaseMinion {

    // This Minion type is not tested yet.

    protected function initializeProfession(): ?Profession {
        return ProfessionRegistry::get("farming");
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
            $wheat = VanillaItems::WHEAT();
            $seeds = VanillaItems::WHEAT_SEEDS();
            
            $wheatAdded = $this->addItemToInventory($wheat);
            $seedsAdded = $this->addItemToInventory($seeds);
            if (!$wheatAdded) {
                $world->dropItem($blockPos, $wheat);
            }
            if (!$seedsAdded) {
                $world->dropItem($blockPos, $seeds);
            }
        }
    }
    public function getSaveId(): string {
        return "wheat_minion";
    }
}