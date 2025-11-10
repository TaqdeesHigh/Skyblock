<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\MinionTypes\mining;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\minions\professions\Profession;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;

class IronMinion extends BaseMinion {

    protected function initializeProfession(): ?Profession {
        return ProfessionRegistry::get("mining");
    }

    protected function canWorkOnBlock(Vector3 $blockPos): bool {
        $world = $this->getWorld();
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        $minionPos = $this->getPosition();
        $belowMinion = new Vector3(floor($minionPos->x), floor($minionPos->y - 1), floor($minionPos->z));
        $targetPos = new Vector3(floor($blockPos->x), floor($blockPos->y), floor($blockPos->z));
        
        if ($targetPos->equals($belowMinion)) {
            return false;
        }
        
        return $block->getTypeId() === VanillaBlocks::IRON_ORE()->getTypeId();
    }

    protected function doWork(): void {
        if ($this->targetBlock === null) return;
        
        $world = $this->getWorld();
        $blockPos = $this->targetBlock;
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        
        if ($block->getTypeId() === VanillaBlocks::IRON_ORE()->getTypeId()) {
            $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::AIR());
            $rawIron = VanillaItems::RAW_IRON();
            $added = $this->addItemToInventory($rawIron);
            if (!$added) {
                $world->dropItem($blockPos, $rawIron);
            }
            
            $this->scheduleBlockRegeneration($blockPos);
        }
    }

    private function scheduleBlockRegeneration(Vector3 $blockPos): void {
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new class($this->getWorld(), $blockPos) extends \pocketmine\scheduler\Task {
                private $world;
                private $blockPos;
                
                public function __construct($world, Vector3 $blockPos) {
                    $this->world = $world;
                    $this->blockPos = $blockPos;
                }
                
                public function onRun(): void {
                    if ($this->world->isLoaded()) {
                        $currentBlock = $this->world->getBlockAt(
                            (int)$this->blockPos->x, 
                            (int)$this->blockPos->y, 
                            (int)$this->blockPos->z
                        );
                        if ($currentBlock->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
                            $this->world->setBlockAt(
                                (int)$this->blockPos->x, 
                                (int)$this->blockPos->y, 
                                (int)$this->blockPos->z, 
                                VanillaBlocks::IRON_ORE()
                            );
                        }
                    }
                }
            },
            60
        );
    }

    protected function generatePlatform(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $platformY = floor($pos->y - 1);
        
        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                $blockPos = new Vector3(
                    floor($pos->x) + $x, 
                    $platformY, 
                    floor($pos->z) + $z
                );
                
                $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
                if ($block->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
                    $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::IRON_ORE());
                }
            }
        }
    }

    public function getSaveId(): string {
        return "iron_minion";
    }
}