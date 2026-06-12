<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\math\Vector3;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
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

    public function onUpdate(int $currentTick): bool {
        $this->handleMovement();
        $this->handleAutoSave($currentTick);
        $this->handleWork($currentTick);
        $result = parent::onUpdate($currentTick);
        $this->enforcePosition();
        return $result;
    }

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
                $pk = LevelEventPacket::create(LevelEvent::BLOCK_START_BREAK, $this->breakingStage * 6553, $this->targetBlock);
                $world->broadcastPacketToViewers($this->targetBlock, $pk);
                if ($this->breakingStage % 2 === 0) {
                    $world->addParticle($this->targetBlock->add(0.5, 0.5, 0.5), new BlockPunchParticle($block, Facing::UP));
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
                $pk = LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, 0, $this->targetBlock);
                $this->getWorld()->broadcastPacketToViewers($this->targetBlock, $pk);
            } catch (\Exception $e) {}
        }
        $this->breakingTick = 0;
        $this->targetBlock = null;
        $this->breakingStage = 0;
        $this->lastBreakingStage = -1;
    }

    protected function findWork(): void {
        if ($this->isInventoryFull()) return;

        $world = $this->getWorld();
        $pos = $this->getPosition();
        $workPositions = [];
        $platformY = floor($pos->y - 1);

        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                if ($x == 0 && $z == 0) continue;
                $blockPos = new Vector3(floor($pos->x) + $x, $platformY, floor($pos->z) + $z);
                if ($this->canWorkOnBlock($blockPos)) {
                    $workPositions[] = $blockPos;
                }
            }
        }

        if (!empty($workPositions)) {
            $this->fillMissingPlatformBlocks();

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
    protected function fillMissingPlatformBlocks(): void {
        $world  = $this->getWorld();
        $pos    = $this->getPosition();

        $isForaging = $this->profession !== null && $this->profession->getName() === "Woodcutting";

        $baseBlock    = $this->getBaseBlock();
        $harvestBlock = $this->getHarvestBlock();

        $platformY = (int)floor($pos->y - 1);

        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                $isCenter = ($x === 0 && $z === 0);

                $basePos = new Vector3(
                    floor($pos->x) + $x,
                    $platformY,
                    floor($pos->z) + $z
                );
                $existing = $world->getBlockAt((int)$basePos->x, (int)$basePos->y, (int)$basePos->z);
                if ($existing->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
                    $world->setBlockAt((int)$basePos->x, (int)$basePos->y, (int)$basePos->z, $baseBlock);
                }

                if ($harvestBlock !== null && !$isCenter) {
                    $harvestY = $isForaging
                        ? (int)floor($pos->y)  
                        : (int)$basePos->y + 1;  

                    $harvestPos    = new Vector3((int)$basePos->x, $harvestY, (int)$basePos->z);
                    $harvestExisting = $world->getBlockAt((int)$harvestPos->x, (int)$harvestPos->y, (int)$harvestPos->z);
                    if ($harvestExisting->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
                        $world->setBlockAt((int)$harvestPos->x, (int)$harvestPos->y, (int)$harvestPos->z, $harvestBlock);
                    }
                }
            }
        }
    }

    protected function generatePlatform(): void {
        $this->fillMissingPlatformBlocks();
    }

    protected function getBaseBlock(): Block {
        if ($this->profession === null) {
            return VanillaBlocks::COBBLESTONE();
        }
        return match($this->profession->getName()) {
            "Farming"    => (function() {
                $f = VanillaBlocks::FARMLAND();
                $f->setWetness(7);
                return $f;
            })(),
            "Woodcutting" => VanillaBlocks::DIRT(),
            default       => VanillaBlocks::COBBLESTONE(),
        };
    }

    protected function getHarvestBlock(): ?Block {
        $typeId = $this->getHarvestBlockType();
        if ($typeId === null) return null;

        foreach (VanillaBlocks::getAll() as $block) {
            if ($block->getTypeId() === $typeId) {
                return $block;
            }
        }
        return null;
    }

    protected function getHarvestBlockType(): ?int {
        return null;
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