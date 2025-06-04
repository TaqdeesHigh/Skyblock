<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\components;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use taqdees\Skyblock\Main;

class ChestGenerator {

    private Main $plugin;
    private const SURFACE_LEVEL = 3;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function placeChest(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        $chestX = $centerX + 2;
        $chestZ = $centerZ + 2;
        $chestY = $centerY + self::SURFACE_LEVEL + 1;
        
        $world->setBlockAt($chestX, $chestY, $chestZ, VanillaBlocks::CHEST());
        $this->scheduleChestFill($world, new Position($chestX, $chestY, $chestZ, $world));
    }

    public function scheduleChestFill(World $world, Position $chestPos): void {
        $scheduler = $this->plugin->getScheduler();
        $scheduler->scheduleDelayedTask(new class($world, $chestPos, $this) extends \pocketmine\scheduler\Task {
            private World $world;
            private Position $chestPos;
            private ChestGenerator $generator;
            
            public function __construct(World $world, Position $chestPos, ChestGenerator $generator) {
                $this->world = $world;
                $this->chestPos = $chestPos;
                $this->generator = $generator;
            }
            
            public function onRun(): void {
                $tile = $this->world->getTile($this->chestPos);
                if ($tile instanceof Chest) {
                    $inventory = $tile->getInventory();
                    $this->generator->fillChest($inventory);
                }
            }
        }, 5);
    }

    public function fillChest(Inventory $inventory): void {
        $items = [
            VanillaItems::WATER_BUCKET(),
            VanillaItems::LAVA_BUCKET(),  
            VanillaBlocks::ICE()->asItem()->setCount(4),       
            VanillaBlocks::DIRT()->asItem()->setCount(16),    
            VanillaBlocks::GRASS()->asItem()->setCount(20),  
            VanillaItems::BONE_MEAL()->setCount(8),
            VanillaBlocks::COBBLESTONE()->asItem()->setCount(32),
            VanillaItems::BREAD()->setCount(12),   
            VanillaItems::APPLE()->setCount(8),   
            VanillaBlocks::OAK_SAPLING()->asItem()->setCount(6),
            VanillaItems::WHEAT_SEEDS()->setCount(12),
            VanillaItems::IRON_PICKAXE(),   
            VanillaItems::IRON_AXE(),     
            VanillaItems::IRON_SHOVEL(),  
            VanillaItems::COOKED_MUTTON()->setCount(8), 
            VanillaItems::STRING()->setCount(12),
            VanillaBlocks::SAND()->asItem()->setCount(16),
            VanillaBlocks::OAK_PLANKS()->asItem()->setCount(24),
            VanillaItems::COAL()->setCount(16),
            VanillaBlocks::TORCH()->asItem()->setCount(32),
        ];

        foreach ($items as $index => $item) {
            if ($index < $inventory->getSize()) {
                $inventory->setItem($index, $item);
            }
        }
    }
}