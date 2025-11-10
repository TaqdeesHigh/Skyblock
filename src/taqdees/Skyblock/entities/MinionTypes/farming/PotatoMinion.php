<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\MinionTypes\farming;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\Potato;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\minions\professions\Profession;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;

class PotatoMinion extends BaseMinion {

    protected function initializeProfession(): ?Profession {
        return ProfessionRegistry::get("farming");
    }

    protected function canWorkOnBlock(Vector3 $blockPos): bool {
        $world = $this->getWorld();
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        
        if ($block instanceof Potato) {
            $age = $block->getAge();
            return $age >= 7;
        }
        
        $farmlandBlock = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y - 1, (int)$blockPos->z);
        $canPlant = $block->getTypeId() === VanillaBlocks::AIR()->getTypeId() && 
                   $farmlandBlock->getTypeId() === VanillaBlocks::FARMLAND()->getTypeId();
        
        return $canPlant;
    }

    protected function findWork(): void {
        if ($this->isInventoryFull()) {
            return;
        }
        
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $workPositions = [];
        $platformY = floor($pos->y);
        
        for ($y = 0; $y >= -1; $y--) {
            for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
                for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                    if ($x == 0 && $z == 0) {
                        continue;
                    }
                    
                    $blockPos = new Vector3(
                        floor($pos->x) + $x, 
                        $platformY + $y, 
                        floor($pos->z) + $z
                    );
                    
                    if ($this->canWorkOnBlock($blockPos)) {
                        $workPositions[] = $blockPos;
                    }
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

    protected function doWork(): void {
        if ($this->targetBlock === null) return;
        
        $world = $this->getWorld();
        $blockPos = $this->targetBlock;
        $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
        
        if ($block instanceof Potato && $block->getAge() >= 7) {
            $this->plugin->getMinionCropHandler()->unregisterMinionCrop(
                $blockPos, 
                $world->getFolderName()
            );
            
            $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::AIR());
            
            $potato = VanillaItems::POTATO();
            $potatoAdded = $this->addItemToInventory($potato);
            
            if (!$potatoAdded) {
                $world->dropItem($blockPos, $potato);
            }
            
            $newPotato = VanillaBlocks::POTATOES();
            $newPotato->setAge(0);
            $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, $newPotato);
            
            $this->plugin->getMinionCropHandler()->registerMinionCrop(
                $blockPos, 
                $world->getFolderName()
            );
        }
        else if ($block->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
            $farmlandBlock = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y - 1, (int)$blockPos->z);
            if ($farmlandBlock->getTypeId() === VanillaBlocks::FARMLAND()->getTypeId()) {
                $newPotato = VanillaBlocks::POTATOES();
                $newPotato->setAge(0);
                $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, $newPotato);
                
                $this->plugin->getMinionCropHandler()->registerMinionCrop(
                    $blockPos, 
                    $world->getFolderName()
                );
            }
        }
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
                    $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::FARMLAND());
                    
                    $potatoBlock = VanillaBlocks::POTATOES();
                    $potatoBlock->setAge(0);
                    $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y + 1, (int)$blockPos->z, $potatoBlock);
                    
                    $cropPos = new Vector3(
                        floor($pos->x) + $x,
                        $platformY + 1,
                        floor($pos->z) + $z
                    );
                    $this->plugin->getMinionCropHandler()->registerMinionCrop(
                        $cropPos,
                        $world->getFolderName()
                    );
                }
            }
        }
    }

    public function onDispose(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        
        $this->plugin->getMinionCropHandler()->clearMinionArea(
            $pos,
            $this->workRadius + 1,
            $world->getFolderName()
        );
        
        parent::onDispose();
    }

    public function getSaveId(): string {
        return "potato_minion";
    }
}