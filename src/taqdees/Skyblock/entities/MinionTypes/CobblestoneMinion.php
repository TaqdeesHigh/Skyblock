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

    protected function doWork(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                for ($y = -2; $y <= 2; $y++) {
                    $blockPos = $pos->add($x, $y, $z);
                    $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
                    
                    if ($block->getTypeId() === VanillaBlocks::COBBLESTONE()->getTypeId()) {
                        $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::AIR());
                        $world->dropItem($blockPos, VanillaBlocks::COBBLESTONE()->asItem());
                        return;
                    }
                }
            }
        }
    }

    public function getSaveId(): string {
        return "cobblestone_minion";
    }
}