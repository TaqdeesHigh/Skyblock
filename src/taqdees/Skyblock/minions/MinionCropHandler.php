<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions;

use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat;
use pocketmine\block\Carrot;
use pocketmine\block\Potato;
use pocketmine\block\Beetroot;
use pocketmine\block\Farmland;
use taqdees\Skyblock\Main;

class MinionCropHandler {

    private Main $plugin;
    private array $minionCrops = [];
    private array $minionFarmland = [];
    private int $tickCounter = 0;
    private const GROWTH_INTERVAL = 100;
    private const FARMLAND_CHECK_INTERVAL = 20; 

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->startGrowthTask();
        $this->startFarmlandProtectionTask();
    }

    public function registerMinionCrop(Vector3 $position, string $worldName): void {
        $key = $this->getPositionKey($position, $worldName);
        $this->minionCrops[$key] = [
            'position' => $position,
            'world' => $worldName,
            'lastGrowth' => time()
        ];
        
        $farmlandPos = $position->subtract(0, 1, 0);
        $this->registerMinionFarmland($farmlandPos, $worldName);
    }

    public function registerMinionFarmland(Vector3 $position, string $worldName): void {
        $key = $this->getPositionKey($position, $worldName);
        $this->minionFarmland[$key] = [
            'position' => $position,
            'world' => $worldName
        ];
    }

    public function unregisterMinionCrop(Vector3 $position, string $worldName): void {
        $key = $this->getPositionKey($position, $worldName);
        unset($this->minionCrops[$key]);
        
        $farmlandPos = $position->subtract(0, 1, 0);
        $this->unregisterMinionFarmland($farmlandPos, $worldName);
    }

    public function unregisterMinionFarmland(Vector3 $position, string $worldName): void {
        $key = $this->getPositionKey($position, $worldName);
        unset($this->minionFarmland[$key]);
    }

    public function isMinionCrop(Vector3 $position, string $worldName): bool {
        $key = $this->getPositionKey($position, $worldName);
        return isset($this->minionCrops[$key]);
    }

    public function isMinionFarmland(Vector3 $position, string $worldName): bool {
        $key = $this->getPositionKey($position, $worldName);
        return isset($this->minionFarmland[$key]);
    }

    private function startGrowthTask(): void {
        $this->plugin->getScheduler()->scheduleRepeatingTask(
            new \pocketmine\scheduler\ClosureTask(function(): void {
                $this->tickCounter++;
                if ($this->tickCounter >= self::GROWTH_INTERVAL) {
                    $this->processCropGrowth();
                    $this->tickCounter = 0;
                }
            }), 1
        );
    }

    private function startFarmlandProtectionTask(): void {
        $this->plugin->getScheduler()->scheduleRepeatingTask(
            new \pocketmine\scheduler\ClosureTask(function(): void {
                $this->protectFarmland();
            }), self::FARMLAND_CHECK_INTERVAL
        );
    }

    private function protectFarmland(): void {
        foreach ($this->minionFarmland as $key => $farmlandData) {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($farmlandData['world']);
            if ($world === null) {
                unset($this->minionFarmland[$key]);
                continue;
            }

            $position = $farmlandData['position'];
            $block = $world->getBlockAt(
                (int)$position->x,
                (int)$position->y,
                (int)$position->z
            );

            if (!($block instanceof Farmland)) {
                $cropPos = $position->add(0, 1, 0);
                $cropBlock = $world->getBlockAt(
                    (int)$cropPos->x,
                    (int)$cropPos->y,
                    (int)$cropPos->z
                );
                
                if ($this->isCropBlock($cropBlock) || $cropBlock->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
                    $farmland = VanillaBlocks::FARMLAND();
                    $farmland->setWetness(7);
                    $world->setBlockAt(
                        (int)$position->x,
                        (int)$position->y,
                        (int)$position->z,
                        $farmland
                    );
                } else {
                    unset($this->minionFarmland[$key]);
                }
            } else {
                if ($block->getWetness() < 7) {
                    $block->setWetness(7);
                    $world->setBlockAt(
                        (int)$position->x,
                        (int)$position->y,
                        (int)$position->z,
                        $block
                    );
                }
            }
        }
    }

    private function processCropGrowth(): void {
        foreach ($this->minionCrops as $key => $cropData) {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($cropData['world']);
            if ($world === null) {
                unset($this->minionCrops[$key]);
                continue;
            }

            $position = $cropData['position'];
            $block = $world->getBlockAt(
                (int)$position->x,
                (int)$position->y,
                (int)$position->z
            );

            if (!$this->isCropBlock($block)) {
                unset($this->minionCrops[$key]);
                continue;
            }

            $this->growCrop($world, $position, $block);
        }
    }

    private function growCrop(World $world, Vector3 $position, $block): void {
        $currentAge = 0;
        $maxAge = 7;

        if ($block instanceof Wheat || $block instanceof Carrot || 
            $block instanceof Potato || $block instanceof Beetroot) {
            $currentAge = $block->getAge();
            $maxAge = 7;
        }

        if ($currentAge >= $maxAge) {
            return;
        }
        if (mt_rand(1, 100) <= 60) {
            $newAge = min($currentAge + 1, $maxAge);
            $block->setAge($newAge);
            $world->setBlockAt(
                (int)$position->x,
                (int)$position->y,
                (int)$position->z,
                $block
            );
        }
    }

    private function isCropBlock($block): bool {
        return $block instanceof Wheat || 
               $block instanceof Carrot || 
               $block instanceof Potato || 
               $block instanceof Beetroot;
    }

    private function getPositionKey(Vector3 $position, string $worldName): string {
        return $worldName . ":" . 
               (int)$position->x . ":" . 
               (int)$position->y . ":" . 
               (int)$position->z;
    }

    public function getAllMinionCrops(): array {
        return $this->minionCrops;
    }

    public function getAllMinionFarmland(): array {
        return $this->minionFarmland;
    }

    public function clearWorldCrops(string $worldName): void {
        foreach ($this->minionCrops as $key => $cropData) {
            if ($cropData['world'] === $worldName) {
                unset($this->minionCrops[$key]);
            }
        }
        
        foreach ($this->minionFarmland as $key => $farmlandData) {
            if ($farmlandData['world'] === $worldName) {
                unset($this->minionFarmland[$key]);
            }
        }
    }

    public function clearMinionArea(Vector3 $center, float $radius, string $worldName): void {
        foreach ($this->minionCrops as $key => $cropData) {
            if ($cropData['world'] === $worldName && 
                $cropData['position']->distance($center) <= $radius) {
                unset($this->minionCrops[$key]);
            }
        }
        
        foreach ($this->minionFarmland as $key => $farmlandData) {
            if ($farmlandData['world'] === $worldName && 
                $farmlandData['position']->distance($center) <= $radius) {
                unset($this->minionFarmland[$key]);
            }
        }
    }
}