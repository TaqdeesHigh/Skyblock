<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\block\tile\Chest;
use pocketmine\inventory\Inventory;
use taqdees\Skyblock\Main;

class IslandGenerator {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function generateIsland(World $world, Position $center): bool {
        try {
            $this->generateIslandStructure($world, $center);
            $this->placeStarterChest($world, $center);
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to generate island: " . $e->getMessage());
            return false;
        }
    }

    private function generateIslandStructure(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();
        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dz = -1; $dz <= 1; $dz++) {
                $world->setBlockAt($x + $dx, $y - 1, $z + $dz, VanillaBlocks::BEDROCK());
            }
        }
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                if (abs($dx) == 2 || abs($dz) == 2) {
                    $world->setBlockAt($x + $dx, $y, $z + $dz, VanillaBlocks::DIRT());
                } else {
                    $world->setBlockAt($x + $dx, $y, $z + $dz, VanillaBlocks::DIRT());
                    $world->setBlockAt($x + $dx, $y + 1, $z + $dz, VanillaBlocks::GRASS());
                }
            }
        }
    }

    private function placeStarterChest(World $world, Position $center): void {
        $chestPos = $center->add(1, 2, 1);
        $world->setBlockAt((int)$chestPos->getX(), (int)$chestPos->getY(), (int)$chestPos->getZ(), VanillaBlocks::CHEST());
        
        $tile = $world->getTile($chestPos);
        if ($tile instanceof Chest) {
            $inventory = $tile->getInventory();
            $this->fillStarterChest($inventory);
        }
    }
    // Added this function for testing and real items will be given when i finish the actual generation of the island.
    private function fillStarterChest(Inventory $inventory): void {
        $starterItems = $this->plugin->getConfigValue('island.starter_items', []);
        
        if (empty($starterItems)) {
            $items = [
                VanillaItems::BREAD()->setCount(8),
            ];
        } else {
            $items = $this->parseConfigItems($starterItems);
        }

        foreach ($items as $index => $item) {
            if ($index < $inventory->getSize()) {
                $inventory->setItem($index, $item);
            }
        }
    }

    private function parseConfigItems(array $configItems): array {
        $items = [];
        foreach ($configItems as $itemString) {
            $parts = explode(':', $itemString);
            $itemName = $parts[0];
            $count = isset($parts[1]) ? (int)$parts[1] : 1;
            $item = $this->getItemFromString($itemName);
            if ($item !== null) {
                $items[] = $item->setCount($count);
            }
        }
        return $items;
    }
}