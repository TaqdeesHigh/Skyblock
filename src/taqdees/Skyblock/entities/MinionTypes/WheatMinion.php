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

    protected function doWork(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                $blockPos = $pos->add($x, 0, $z);
                $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
                if ($block->getTypeId() === VanillaBlocks::WHEAT()->getTypeId()) {
                    $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::WHEAT());
                    $world->dropItem($blockPos, VanillaItems::WHEAT());
                    $world->dropItem($blockPos, VanillaItems::WHEAT_SEEDS());
                    return;
                }
            }
        }
    }

    public function getSaveId(): string {
        return "wheat_minion";
    }
}