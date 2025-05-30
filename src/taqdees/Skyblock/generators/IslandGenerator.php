<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\block\tile\Chest;
use pocketmine\inventory\Inventory;
use pocketmine\block\Stair;
use taqdees\Skyblock\Main;

class IslandGenerator {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function generateIsland(World $world, Position $center): bool {
        try {
            $this->generateMainIsland($world, $center);
            $this->placeTree($world, $center);
            $this->generateSecondIsland($world, $center);
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to generate island: " . $e->getMessage());
            return false;
        }
    }

    private function generateMainIsland(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();
        for ($dx = -8; $dx <= 8; $dx++) {
            for ($dz = -8; $dz <= 8; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= 8) {
                    $stoneDepth = $distance <= 4 ? 4 : ($distance <= 6 ? 3 : 2);
                    for ($dy = -$stoneDepth; $dy < 0; $dy++) {
                        if ($distance <= 7.5 - ($dy * -0.3)) {
                            $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::STONE());
                        }
                    }
                    $dirtHeight = $distance <= 3 ? 2 : 1;
                    for ($dy = 0; $dy < $dirtHeight; $dy++) {
                        if ($distance <= 7 - ($dy * 0.5)) {
                            $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::DIRT());
                        }
                    }
                    if ($distance <= 6.5) {
                        $grassY = $y + $dirtHeight;
                        if ($distance <= 2 && mt_rand(1, 4) == 1) {
                            $grassY++;
                        }
                        $world->setBlockAt($x + $dx, $grassY, $z + $dz, VanillaBlocks::GRASS());
                        if (mt_rand(1, 8) == 1) {
                            $world->setBlockAt($x + $dx, $grassY + 1, $z + $dz, VanillaBlocks::TALL_GRASS());
                        }
                    }
                }
            }
        }
        for ($i = 0; $i < 6; $i++) {
            $randX = $x + mt_rand(-6, 6);
            $randZ = $z + mt_rand(-6, 6);
            $distance = sqrt(($randX - $x) * ($randX - $x) + ($randZ - $z) * ($randZ - $z));
            
            if ($distance <= 6) {
                for ($checkY = $y + 5; $checkY >= $y - 5; $checkY--) {
                    $block = $world->getBlockAt($randX, $checkY, $randZ);
                    if ($block->getTypeId() === VanillaBlocks::GRASS()->getTypeId()) {
                        $world->setBlockAt($randX, $checkY, $randZ, VanillaBlocks::DIRT());
                        break;
                    }
                }
            }
        }
    }

    private function generateSecondIsland(World $world, Position $center): void {
        $x = (int)$center->getX() + 64;
        $y = (int)$center->getY();
        $z = (int)$center->getZ();
        for ($dx = -12; $dx <= 12; $dx++) {
            for ($dz = -12; $dz <= 12; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= 12) {
                    $stoneDepth = $distance <= 8 ? 6 : ($distance <= 10 ? 4 : 2);
                    for ($dy = -$stoneDepth; $dy <= 0; $dy++) {
                        if ($distance <= 11.5 - ($dy * -0.2)) {
                            $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::STONE());
                        }
                    }
                    if ($distance <= 11) {
                        $dirtHeight = $distance <= 6 ? 2 : 1;
                        for ($dy = 1; $dy <= $dirtHeight; $dy++) {
                            $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::DIRT());
                        }
                    }
                    if ($distance <= 10.5) {
                        $grassY = $y + ($distance <= 6 ? 3 : 2);
                        $world->setBlockAt($x + $dx, $grassY, $z + $dz, VanillaBlocks::GRASS());
                        if ($distance > 4 && mt_rand(1, 10) == 1) {
                            $world->setBlockAt($x + $dx, $grassY + 1, $z + $dz, VanillaBlocks::TALL_GRASS());
                        }
                    }
                }
            }
        }
        for ($dx = -8; $dx <= 8; $dx++) {
            for ($dz = -8; $dz <= 8; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                if ($distance <= 8 && $distance > 4 && mt_rand(1, 6) == 1) {
                    $grassY = $y + ($distance <= 6 ? 3 : 2);
                    $world->setBlockAt($x + $dx, $grassY, $z + $dz, VanillaBlocks::COBBLESTONE());
                }
            }
        }
        $this->createPortalArea($world, $x, $y, $z);
        $this->createMinionArea($world, $x, $y, $z);
        $this->createSideMineshaftEntrance($world, $x, $y, $z);
        $this->generateMineshaft($world, new Position($x, $y, $z, $world));
    }

    private function createPortalArea(World $world, int $x, int $y, int $z): void {
        $portalX = $x;
        $portalZ = $z + 4;
        $portalY = $y + 3;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($portalX + $dx, $portalY, $portalZ + $dz, VanillaBlocks::STONE_BRICKS());
            }
        }
        $corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
        foreach ($corners as $corner) {
            for ($dy = 1; $dy <= 3; $dy++) {
                $world->setBlockAt($portalX + $corner[0], $portalY + $dy, $portalZ + $corner[1], VanillaBlocks::STONE_BRICKS());
            }
        }
    }

    private function createMinionArea(World $world, int $x, int $y, int $z): void {
        $minionX = $x;
        $minionZ = $z - 4;
        $minionY = $y + 3;
        for ($dx = -3; $dx <= 3; $dx++) {
            for ($dz = -3; $dz <= 3; $dz++) {
                $world->setBlockAt($minionX + $dx, $minionY, $minionZ + $dz, VanillaBlocks::GRASS());
            }
        }
        for ($dx = -3; $dx <= 3; $dx++) {
            $world->setBlockAt($minionX + $dx, $minionY, $minionZ, VanillaBlocks::COBBLESTONE());
        }
        for ($dz = -3; $dz <= 3; $dz++) {
            $world->setBlockAt($minionX, $minionY, $minionZ + $dz, VanillaBlocks::COBBLESTONE());
        }
    }

    private function createSideMineshaftEntrance(World $world, int $x, int $y, int $z): void {
        $entranceX = $x - 10;
        $entranceY = $y + 2;
        $entranceZ = $z;
        for ($clearX = 0; $clearX <= 2; $clearX++) {
            for ($clearY = 0; $clearY <= 3; $clearY++) {
                $world->setBlockAt($entranceX + $clearX, $entranceY + $clearY, $entranceZ, VanillaBlocks::AIR());
                $world->setBlockAt($entranceX + $clearX, $entranceY + $clearY, $entranceZ + 1, VanillaBlocks::AIR());
                $world->setBlockAt($entranceX + $clearX, $entranceY + $clearY, $entranceZ - 1, VanillaBlocks::AIR());
            }
        }
        for ($frameY = 0; $frameY <= 3; $frameY++) {
            $world->setBlockAt($entranceX, $entranceY + $frameY, $entranceZ + 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($entranceX, $entranceY + $frameY, $entranceZ - 2, VanillaBlocks::OAK_PLANKS());
        }
        for ($frameZ = -2; $frameZ <= 2; $frameZ++) {
            $world->setBlockAt($entranceX, $entranceY + 3, $entranceZ + $frameZ, VanillaBlocks::OAK_PLANKS());
        }
        $currentX = $entranceX + 1;
        $currentY = $entranceY;
        
        for ($step = 0; $step < 8; $step++) {
            $currentX++;
            $currentY--;
            $stair = VanillaBlocks::OAK_STAIRS();
            if ($stair instanceof Stair) {
                $stair->setFacing(2);
            }
            $world->setBlockAt($currentX, $currentY, $entranceZ, $stair);
            for ($clearHeight = 1; $clearHeight <= 3; $clearHeight++) {
                $world->setBlockAt($currentX, $currentY + $clearHeight, $entranceZ, VanillaBlocks::AIR());
                $world->setBlockAt($currentX, $currentY + $clearHeight, $entranceZ + 1, VanillaBlocks::AIR());
                $world->setBlockAt($currentX, $currentY + $clearHeight, $entranceZ - 1, VanillaBlocks::AIR());
            }
            $world->setBlockAt($currentX, $currentY, $entranceZ + 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($currentX, $currentY, $entranceZ - 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($currentX, $currentY + 1, $entranceZ + 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($currentX, $currentY + 1, $entranceZ - 2, VanillaBlocks::OAK_PLANKS());
            if ($step % 3 == 0) {
                $world->setBlockAt($currentX, $currentY + 2, $entranceZ + 1, VanillaBlocks::TORCH());
                $world->setBlockAt($currentX, $currentY + 2, $entranceZ - 1, VanillaBlocks::TORCH());
            }
        }
    }

    private function generateMineshaft(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY() - 6;
        $z = (int)$center->getZ();
        $width = 7;
        $height = 4;
        for ($dx = -3; $dx <= 3; $dx++) {
            for ($dz = -3; $dz <= 3; $dz++) {
                for ($dy = 0; $dy <= $height; $dy++) {
                    $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::AIR());
                }
            }
        }
        for ($dx = -4; $dx <= 4; $dx++) {
            for ($dz = -4; $dz <= 4; $dz++) {
                $world->setBlockAt($x + $dx, $y - 1, $z + $dz, VanillaBlocks::STONE());
                $world->setBlockAt($x + $dx, $y + $height + 1, $z + $dz, VanillaBlocks::STONE());
                if (abs($dx) == 4 || abs($dz) == 4) {
                    for ($dy = 0; $dy <= $height; $dy++) {
                        $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::STONE());
                    }
                }
            }
        }
        $corners = [[-3, -3], [3, -3], [-3, 3], [3, 3]];
        foreach ($corners as $corner) {
            for ($dy = 0; $dy <= $height; $dy++) {
                $world->setBlockAt($x + $corner[0], $y + $dy, $z + $corner[1], VanillaBlocks::OAK_PLANKS());
            }
        }
        for ($dx = -2; $dx <= 2; $dx += 4) {
            for ($dy = 0; $dy <= $height; $dy++) {
                $world->setBlockAt($x + $dx, $y + $dy, $z, VanillaBlocks::OAK_PLANKS());
            }
        }
        for ($dz = -2; $dz <= 2; $dz += 4) {
            for ($dy = 0; $dy <= $height; $dy++) {
                $world->setBlockAt($x, $y + $dy, $z + $dz, VanillaBlocks::OAK_PLANKS());
            }
        }
        $chestX = $x;
        $chestY = $y;
        $chestZ = $z + 2;
        
        $world->setBlockAt($chestX, $chestY, $chestZ, VanillaBlocks::CHEST());
        $this->scheduleChestFill($world, new Position($chestX, $chestY, $chestZ, $world));
        $torchPositions = [
            [$x - 2, $y + 2, $z - 2],
            [$x + 2, $y + 2, $z - 2],
            [$x - 2, $y + 2, $z + 2],
            [$x + 2, $y + 2, $z + 2],
            [$x, $y + 2, $z - 2],
            [$x, $y + 2, $z + 2],
        ];
        
        foreach ($torchPositions as $pos) {
            $world->setBlockAt($pos[0], $pos[1], $pos[2], VanillaBlocks::TORCH());
        }
    }

    private function scheduleChestFill(World $world, Position $chestPos): void {
        $scheduler = $this->plugin->getScheduler();
        $scheduler->scheduleDelayedTask(new class($world, $chestPos, $this) extends \pocketmine\scheduler\Task {
            private World $world;
            private Position $chestPos;
            private IslandGenerator $generator;
            
            public function __construct(World $world, Position $chestPos, IslandGenerator $generator) {
                $this->world = $world;
                $this->chestPos = $chestPos;
                $this->generator = $generator;
            }
            
            public function onRun(): void {
                $tile = $this->world->getTile($this->chestPos);
                if ($tile instanceof Chest) {
                    $inventory = $tile->getInventory();
                    $this->generator->fillCaveChest($inventory);
                }
            }
        }, 5);
    }

    public function fillCaveChest(Inventory $inventory): void {
        $items = [
            VanillaItems::WATER_BUCKET(),
            VanillaItems::LAVA_BUCKET(),  
            VanillaBlocks::ICE()->asItem()->setCount(2),       
            VanillaBlocks::DIRT()->asItem()->setCount(8),    
            VanillaBlocks::GRASS()->asItem()->setCount(12),  
            VanillaItems::BONE_MEAL()->setCount(5),
            VanillaBlocks::COBBLESTONE()->asItem()->setCount(16),
            VanillaItems::BREAD()->setCount(8),   
            VanillaItems::APPLE()->setCount(6),   
            VanillaBlocks::OAK_SAPLING()->asItem()->setCount(4),
            VanillaItems::WHEAT_SEEDS()->setCount(8),
            VanillaItems::STONE_PICKAXE(),   
            VanillaItems::STONE_AXE(),     
            VanillaItems::STONE_SHOVEL(),  
            VanillaItems::COOKED_MUTTON()->setCount(4), 
            VanillaItems::STRING()->setCount(6),
            VanillaBlocks::SAND()->asItem()->setCount(8),
            VanillaBlocks::OAK_PLANKS()->asItem()->setCount(12),
            VanillaItems::COAL()->setCount(8),
            VanillaBlocks::TORCH()->asItem()->setCount(16),
        ];

        foreach ($items as $index => $item) {
            if ($index < $inventory->getSize()) {
                $inventory->setItem($index, $item);
            }
        }
    }

    private function placeTree(World $world, Position $center): void {
        $treeX = (int)$center->getX() + 3;
        $treeY = (int)$center->getY() + 3; 
        $treeZ = (int)$center->getZ() - 2;
        for ($checkY = $treeY + 3; $checkY >= $treeY - 3; $checkY--) {
            $block = $world->getBlockAt($treeX, $checkY, $treeZ);
            if ($block->getTypeId() === VanillaBlocks::GRASS()->getTypeId()) {
                $treeY = $checkY + 1;
                break;
            }
        }
        for ($i = 0; $i < 6; $i++) {
            $world->setBlockAt($treeX, $treeY + $i, $treeZ, VanillaBlocks::OAK_LOG());
        }
        $leafY = $treeY + 3;
        for ($dx = -3; $dx <= 3; $dx++) {
            for ($dz = -3; $dz <= 3; $dz++) {
                for ($dy = 0; $dy <= 3; $dy++) {
                    $distance = sqrt($dx * $dx + $dz * $dz + ($dy * 0.5) * ($dy * 0.5));
                    if ($distance <= 3 && !($dx == 0 && $dz == 0 && $dy <= 2)) {
                        $chance = $distance <= 2 ? 9 : ($distance <= 2.5 ? 7 : 5);
                        if (mt_rand(1, 10) <= $chance) {
                            $world->setBlockAt($treeX + $dx, $leafY + $dy, $treeZ + $dz, VanillaBlocks::OAK_LEAVES());
                        }
                    }
                }
            }
        }
        $world->setBlockAt($treeX, $leafY + 4, $treeZ, VanillaBlocks::OAK_LEAVES());
        $world->setBlockAt($treeX + 1, $leafY + 4, $treeZ, VanillaBlocks::OAK_LEAVES());
        $world->setBlockAt($treeX - 1, $leafY + 4, $treeZ, VanillaBlocks::OAK_LEAVES());
        $world->setBlockAt($treeX, $leafY + 4, $treeZ + 1, VanillaBlocks::OAK_LEAVES());
        $world->setBlockAt($treeX, $leafY + 4, $treeZ - 1, VanillaBlocks::OAK_LEAVES());
    }
    public function getSpawnPosition(Position $center): Position {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();
        $world = $center->getWorld();
        for ($checkY = $y + 10; $checkY >= $y - 5; $checkY--) {
            $block = $world->getBlockAt($x, $checkY, $z);
            if ($block->getTypeId() === VanillaBlocks::GRASS()->getTypeId()) {
                return new Position($x + 0.5, $checkY + 1, $z + 0.5, $world);
            }
        }
        return new Position($x + 0.5, $y + 3, $z + 0.5, $world);
    }
}