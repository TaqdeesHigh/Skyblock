<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Stair;
use pocketmine\block\tile\Chest;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use taqdees\Skyblock\Main;

class MineshaftStructure {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function generateEntrance(World $world, int $x, int $y, int $z): void {
        $entranceX = $x - 10;
        $entranceY = $y + 2;
        $entranceZ = $z;

        $this->clearEntrance($world, $entranceX, $entranceY, $entranceZ);
        $this->buildEntranceFrame($world, $entranceX, $entranceY, $entranceZ);
        $this->buildStairway($world, $entranceX, $entranceY, $entranceZ);
    }

    private function clearEntrance(World $world, int $x, int $y, int $z): void {
        for ($clearX = 0; $clearX <= 2; $clearX++) {
            for ($clearY = 0; $clearY <= 3; $clearY++) {
                $world->setBlockAt($x + $clearX, $y + $clearY, $z, VanillaBlocks::AIR());
                $world->setBlockAt($x + $clearX, $y + $clearY, $z + 1, VanillaBlocks::AIR());
                $world->setBlockAt($x + $clearX, $y + $clearY, $z - 1, VanillaBlocks::AIR());
            }
        }
    }

    private function buildEntranceFrame(World $world, int $x, int $y, int $z): void {
        for ($frameY = 0; $frameY <= 3; $frameY++) {
            $world->setBlockAt($x, $y + $frameY, $z + 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($x, $y + $frameY, $z - 2, VanillaBlocks::OAK_PLANKS());
        }
        for ($frameZ = -2; $frameZ <= 2; $frameZ++) {
            $world->setBlockAt($x, $y + 3, $z + $frameZ, VanillaBlocks::OAK_PLANKS());
        }
    }

    private function buildStairway(World $world, int $x, int $y, int $z): void {
        $currentX = $x + 1;
        $currentY = $y;
        
        for ($step = 0; $step < 8; $step++) {
            $currentX++;
            $currentY--;
            $stair = VanillaBlocks::OAK_STAIRS();
            if ($stair instanceof Stair) {
                $stair->setFacing(2);
            }
            $world->setBlockAt($currentX, $currentY, $z, $stair);
            for ($clearHeight = 1; $clearHeight <= 3; $clearHeight++) {
                $world->setBlockAt($currentX, $currentY + $clearHeight, $z, VanillaBlocks::AIR());
                $world->setBlockAt($currentX, $currentY + $clearHeight, $z + 1, VanillaBlocks::AIR());
                $world->setBlockAt($currentX, $currentY + $clearHeight, $z - 1, VanillaBlocks::AIR());
            }
            $world->setBlockAt($currentX, $currentY, $z + 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($currentX, $currentY, $z - 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($currentX, $currentY + 1, $z + 2, VanillaBlocks::OAK_PLANKS());
            $world->setBlockAt($currentX, $currentY + 1, $z - 2, VanillaBlocks::OAK_PLANKS());
            if ($step % 3 == 0) {
                $world->setBlockAt($currentX, $currentY + 2, $z + 1, VanillaBlocks::TORCH());
                $world->setBlockAt($currentX, $currentY + 2, $z - 1, VanillaBlocks::TORCH());
            }
        }
    }

    public function generateUnderground(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY() - 6;
        $z = (int)$center->getZ();

        $this->clearMineshaftSpace($world, $x, $y, $z);
        $this->buildMineshaftStructure($world, $x, $y, $z);
        $this->placeChest($world, $x, $y, $z);
        $this->placeTorches($world, $x, $y, $z);
    }

    private function clearMineshaftSpace(World $world, int $x, int $y, int $z): void {
        $height = 4;
        for ($dx = -3; $dx <= 3; $dx++) {
            for ($dz = -3; $dz <= 3; $dz++) {
                for ($dy = 0; $dy <= $height; $dy++) {
                    $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::AIR());
                }
            }
        }
    }

    private function buildMineshaftStructure(World $world, int $x, int $y, int $z): void {
        $height = 4;
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
        $this->buildSupportPillars($world, $x, $y, $z, $height);
    }

    private function buildSupportPillars(World $world, int $x, int $y, int $z, int $height): void {
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
    }

    private function placeChest(World $world, int $x, int $y, int $z): void {
        $chestX = $x;
        $chestY = $y;
        $chestZ = $z + 2;
        
        $world->setBlockAt($chestX, $chestY, $chestZ, VanillaBlocks::CHEST());
        $this->scheduleChestFill($world, new Position($chestX, $chestY, $chestZ, $world));
    }

    private function placeTorches(World $world, int $x, int $y, int $z): void {
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
            private MineshaftStructure $structure;
            
            public function __construct(World $world, Position $chestPos, MineshaftStructure $structure) {
                $this->world = $world;
                $this->chestPos = $chestPos;
                $this->structure = $structure;
            }
            
            public function onRun(): void {
                $tile = $this->world->getTile($this->chestPos);
                if ($tile instanceof Chest) {
                    $inventory = $tile->getInventory();
                    $this->structure->fillCaveChest($inventory);
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
}