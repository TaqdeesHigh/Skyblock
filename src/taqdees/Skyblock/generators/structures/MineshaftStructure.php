<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
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
        $entranceX = $x + 8;
        $entranceY = $y;
        $entranceZ = $z;

        $this->clearSideEntrance($world, $entranceX, $entranceY, $entranceZ);
        $this->buildSideEntrance($world, $entranceX, $entranceY, $entranceZ);
    }

    private function clearSideEntrance(World $world, int $x, int $y, int $z): void {
        for ($dx = 0; $dx <= 4; $dx++) {
            for ($dy = 0; $dy <= 2; $dy++) {
                $world->setBlockAt($x - $dx, $y + $dy, $z, VanillaBlocks::AIR());
                $world->setBlockAt($x - $dx, $y + $dy, $z + 1, VanillaBlocks::AIR());
                $world->setBlockAt($x - $dx, $y + $dy, $z - 1, VanillaBlocks::AIR());
            }
        }
    }

    private function buildSideEntrance(World $world, int $x, int $y, int $z): void {
        for ($dx = 0; $dx <= 4; $dx++) {
            $world->setBlockAt($x - $dx, $y + 3, $z, VanillaBlocks::STONE());
            $world->setBlockAt($x - $dx, $y + 3, $z + 1, VanillaBlocks::STONE());
            $world->setBlockAt($x - $dx, $y + 3, $z - 1, VanillaBlocks::STONE());
            $world->setBlockAt($x - $dx, $y - 1, $z, VanillaBlocks::STONE());
            $world->setBlockAt($x - $dx, $y - 1, $z + 1, VanillaBlocks::STONE());
            $world->setBlockAt($x - $dx, $y - 1, $z - 1, VanillaBlocks::STONE());
            for ($dy = 0; $dy <= 2; $dy++) {
                $world->setBlockAt($x - $dx, $y + $dy, $z + 2, VanillaBlocks::STONE());
                $world->setBlockAt($x - $dx, $y + $dy, $z - 2, VanillaBlocks::STONE());
                if ($dx % 2 == 0) {
                    $world->setBlockAt($x - $dx, $y + $dy, $z + 2, VanillaBlocks::COBBLESTONE());
                    $world->setBlockAt($x - $dx, $y + $dy, $z - 2, VanillaBlocks::COBBLESTONE());
                }
            }
        }
    }

    public function generateUnderground(World $world, Position $center): void {
        $x = (int)$center->getX() + 4;
        $y = (int)$center->getY() - 2;
        $z = (int)$center->getZ();

        $this->clearMineshaftSpace($world, $x, $y, $z);
        $this->buildMineshaftStructure($world, $x, $y, $z);
        $this->placeChest($world, $x, $y, $z);
    }

    private function clearMineshaftSpace(World $world, int $x, int $y, int $z): void {
        $height = 3;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                for ($dy = 0; $dy <= $height; $dy++) {
                    $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::AIR());
                }
            }
        }
    }

    private function buildMineshaftStructure(World $world, int $x, int $y, int $z): void {
        $height = 3;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($x + $dx, $y - 1, $z + $dz, VanillaBlocks::STONE());
                $world->setBlockAt($x + $dx, $y + $height + 1, $z + $dz, VanillaBlocks::STONE());
                if (abs($dx) == 2 || abs($dz) == 2) {
                    for ($dy = 0; $dy <= $height; $dy++) {
                        if (($dx + $dz + $dy) % 2 == 0) {
                            $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::STONE());
                        } else {
                            $world->setBlockAt($x + $dx, $y + $dy, $z + $dz, VanillaBlocks::COBBLESTONE());
                        }
                    }
                }
            }
        }
    }

    private function placeChest(World $world, int $x, int $y, int $z): void {
        $chestX = $x + 1;
        $chestY = $y;
        $chestZ = $z + 1;
        
        $world->setBlockAt($chestX, $chestY, $chestZ, VanillaBlocks::CHEST());
        $this->scheduleChestFill($world, new Position($chestX, $chestY, $chestZ, $world));
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