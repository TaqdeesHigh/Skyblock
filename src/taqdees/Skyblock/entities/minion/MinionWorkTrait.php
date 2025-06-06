<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\math\Vector3;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\BlockPunchParticle;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\BlockPunchSound;
use pocketmine\math\Facing; 

trait MinionWorkTrait {
    protected float $workRadius = 2.0;
    protected int $workCooldown = 20;
    protected int $lastWorkTick = 0;
    protected bool $isWorking = false;
    protected ?Vector3 $targetBlock = null;
    protected int $breakingTick = 0;
    protected int $breakTime = 30;
    protected int $breakCooldown = 100;
    protected int $lastBreakTick = 0;
    protected int $lastAnimationTick = 0;
    protected int $animationInterval = 5;
    protected int $breakingStage = 0;
    protected int $lastBreakingStage = -1;

    protected function updateWorkStats(): void {
        $this->workCooldown = max(5, 20 - ($this->level - 1) * 2);
        $this->breakTime = max(10, 30 - ($this->level - 1) * 2);
        $this->breakCooldown = max(20, 100 - ($this->level - 1) * 8);
        $this->animationInterval = max(3, 8 - ($this->level - 1));
    }

    protected function updateEquipment(): void {
        $tool = $this->getCurrentTool();
        if ($tool !== null) {
            $this->getInventory()->setItemInHand($tool);
        }
    }

    public function getCurrentTool(): ?Item {
        if ($this->profession === null) {
            return null;
        }
        
        $tools = $this->profession->getTools();
        $toolIndex = min($this->level - 1, count($tools) - 1);
        return $tools[$toolIndex] ?? null;
    }

    protected function handleWork(int $currentTick): void {
        if ($this->targetBlock !== null) {
            $this->breakingTick++;
            $this->lookAtBlock($this->targetBlock);
            if ($currentTick - $this->lastAnimationTick >= $this->animationInterval) {
                $this->playBreakingAnimation();
                $this->lastAnimationTick = $currentTick;
            }
            $this->updateBlockBreakingVisual();
            
            if ($this->breakingTick >= $this->breakTime) {
                $this->finishBreaking();
                $this->resetBreaking();
                $this->lastBreakTick = $currentTick;
            }
        } else {
            if ($currentTick - $this->lastBreakTick >= $this->breakCooldown) {
                if ($currentTick - $this->lastWorkTick >= $this->workCooldown) {
                    $this->findWork();
                    $this->lastWorkTick = $currentTick;
                }
            }
        }
    }

    protected function playBreakingAnimation(): void {
        try {
            $pk = new AnimatePacket();
            $pk->actorRuntimeId = $this->getId();
            $pk->action = AnimatePacket::ACTION_SWING_ARM;
            $this->getWorld()->broadcastPacketToViewers($this->location, $pk);
        } catch (\Exception $e) {}
    }

    protected function updateBlockBreakingVisual(): void {
        if ($this->targetBlock === null) return;
        
        try {
            $world = $this->getWorld();
            $block = $world->getBlockAt((int)$this->targetBlock->x, (int)$this->targetBlock->y, (int)$this->targetBlock->z);
            $progress = $this->breakingTick / $this->breakTime;
            $newStage = min(9, (int)($progress * 10));
            if ($newStage !== $this->lastBreakingStage) {
                $this->breakingStage = $newStage;
                $this->lastBreakingStage = $newStage;
                $pk = LevelEventPacket::create(
                    LevelEvent::BLOCK_START_BREAK,
                    $this->breakingStage * 6553,
                    $this->targetBlock
                );
                
                $world->broadcastPacketToViewers($this->targetBlock, $pk);
                if ($this->breakingStage % 2 === 0) {
                    $world->addParticle(
                        $this->targetBlock->add(0.5, 0.5, 0.5), 
                        new BlockPunchParticle($block, Facing::UP)
                    );
                }
                if ($this->breakingStage % 3 === 0) {
                    $world->addSound($this->targetBlock, new BlockPunchSound($block));
                }
            }
        } catch (\Exception $e) {}
    }

    protected function resetBreaking(): void {
        if ($this->targetBlock !== null) {
            try {
                $pk = LevelEventPacket::create(
                    LevelEvent::BLOCK_STOP_BREAK,
                    0,
                    $this->targetBlock
                );
                
                $this->getWorld()->broadcastPacketToViewers($this->targetBlock, $pk);
            } catch (\Exception $e) {}
        }
        
        $this->breakingTick = 0;
        $this->targetBlock = null;
        $this->breakingStage = 0;
        $this->lastBreakingStage = -1;
    }

    protected function findWork(): void {
        if ($this->isInventoryFull()) {
            return;
        }
        
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $workPositions = [];
        $platformY = floor($pos->y - 1);
        
        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                if ($x == 0 && $z == 0) {
                    continue;
                }
                
                $blockPos = new Vector3(
                    floor($pos->x) + $x, 
                    $platformY, 
                    floor($pos->z) + $z
                );
                
                if ($this->canWorkOnBlock($blockPos)) {
                    $workPositions[] = $blockPos;
                }
            }
        }
        
        if (!empty($workPositions)) {
            $randomIndex = array_rand($workPositions);
            $this->targetBlock = $workPositions[$randomIndex];
            $this->breakingTick = 0;
            $this->breakingStage = 0;
            $this->lastBreakingStage = -1;
            $this->rotateTowardsBlock($this->targetBlock);
            return;
        }
        $this->generatePlatform();
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
                    $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::COBBLESTONE());
                }
            }
        }
    }

    protected function canWorkOnBlock(Vector3 $blockPos): bool {
        return false;
    }

    protected function finishBreaking(): void {
        if ($this->targetBlock === null) return;
        
        try {
            $world = $this->getWorld();
            $block = $world->getBlockAt((int)$this->targetBlock->x, (int)$this->targetBlock->y, (int)$this->targetBlock->z);
            $world->addParticle($this->targetBlock->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
            $world->addSound($this->targetBlock, new BlockBreakSound($block));
            
        } catch (\Exception $e) {}
        
        $this->doWork();
    }


    protected function doWork(): void {}
}