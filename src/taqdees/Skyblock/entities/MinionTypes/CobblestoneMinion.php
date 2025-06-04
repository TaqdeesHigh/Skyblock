<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\MinionTypes;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\minions\professions\Profession;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;

class CobblestoneMinion extends BaseMinion {

    protected function initializeProfession(): ?Profession {
        return ProfessionRegistry::get("mining");
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