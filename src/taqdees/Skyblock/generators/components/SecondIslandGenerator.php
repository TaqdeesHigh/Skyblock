<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\components;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\generators\structures\PortalStructure;
use taqdees\Skyblock\generators\structures\MinionAreaStructure;

class SecondIslandGenerator {

    private Main $plugin;
    private PortalStructure $portalStructure;
    private MinionAreaStructure $minionAreaStructure;
    private ChestGenerator $chestGenerator;
    
    private const ISLAND_RADIUS = 6;
    private const STONE_DEPTH = 4;
    private const SURFACE_LEVEL = 2;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->portalStructure = new PortalStructure();
        $this->minionAreaStructure = new MinionAreaStructure($plugin);
        $this->chestGenerator = new ChestGenerator($plugin);
    }

    public function generate(World $world, Position $center, Player $player = null): void {
        $islandCenter = new Position(
            (int)$center->getX() - 64,
            (int)$center->getY(),
            (int)$center->getZ(),
            $world
        );
        
        $this->generateStoneIsland($world, $islandCenter);
        $this->generateUndergroundChamber($world, $islandCenter);
        $this->generateStructures($world, $islandCenter, $player);
        $this->placeUndergroundChest($world, $islandCenter);
        
        if ($player !== null) {
            $this->spawnOzzyNPC($player, $world, $islandCenter);
        }
    }

    private function generateStoneIsland(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        
        for ($dx = -self::ISLAND_RADIUS; $dx <= self::ISLAND_RADIUS; $dx++) {
            for ($dz = -self::ISLAND_RADIUS; $dz <= self::ISLAND_RADIUS; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= self::ISLAND_RADIUS) {
                    $x = $centerX + $dx;
                    $z = $centerZ + $dz;
                    $this->generateIslandColumn($world, $x, $centerY, $z, $distance);
                }
            }
        }
    }

    private function generateIslandColumn(World $world, int $x, int $baseY, int $z, float $distance): void {
        $surfaceHeight = $this->calculateSurfaceHeight($distance);
        $depth = $this->calculateDepth($distance);
        for ($dy = -$depth; $dy <= $surfaceHeight; $dy++) {
            if ($dy == $surfaceHeight) {
                $world->setBlockAt($x, $baseY + $dy, $z, VanillaBlocks::COBBLESTONE());
            } elseif ($dy >= $surfaceHeight - 1) {
                $block = (mt_rand(1, 3) == 1) ? VanillaBlocks::COBBLESTONE() : VanillaBlocks::STONE();
                $world->setBlockAt($x, $baseY + $dy, $z, $block);
            } else {
                $world->setBlockAt($x, $baseY + $dy, $z, VanillaBlocks::STONE());
            }
        }
    }

    private function calculateSurfaceHeight(float $distance): int {
        if ($distance <= 2) {
            return self::SURFACE_LEVEL + 1;
        } elseif ($distance <= 4) {
            return self::SURFACE_LEVEL;
        } else {
            return self::SURFACE_LEVEL - 1;
        }
    }

    private function calculateDepth(float $distance): int {
        if ($distance <= 2) {
            return self::STONE_DEPTH + 1;
        } elseif ($distance <= 4) {
            return self::STONE_DEPTH;
        } else {
            return self::STONE_DEPTH - 1;
        }
    }

    private function generateUndergroundChamber(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        $chamberY = $centerY - 3;
        for ($dx = -1; $dx <= 1; $dx++) {
            for ($dz = -1; $dz <= 1; $dz++) {
                for ($dy = 0; $dy <= 2; $dy++) {
                    $world->setBlockAt($centerX + $dx, $chamberY + $dy, $centerZ + $dz, VanillaBlocks::AIR());
                }
            }
        }
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($centerX + $dx, $chamberY - 1, $centerZ + $dz, VanillaBlocks::STONE_BRICKS());
                if (abs($dx) <= 1 && abs($dz) <= 1) {
                    $world->setBlockAt($centerX + $dx, $chamberY + 3, $centerZ + $dz, VanillaBlocks::STONE_BRICKS());
                }
            }
        }
        for ($dy = 0; $dy <= 2; $dy++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dz = -1; $dz <= 1; $dz++) {
                    if (abs($dx) == 1 || abs($dz) == 1) {
                        if (!(abs($dx) == 1 && abs($dz) == 1)) {
                            $world->setBlockAt($centerX + $dx, $chamberY + $dy, $centerZ + $dz, VanillaBlocks::STONE_BRICKS());
                        }
                    }
                }
            }
        }
        $this->createStaircase($world, $centerX, $centerY, $centerZ);
    }

    private function createStaircase(World $world, int $centerX, int $centerY, int $centerZ): void {
        $stairs = [
            ['x' => $centerX - 2, 'y' => $centerY + 2, 'z' => $centerZ],  
            ['x' => $centerX - 2, 'y' => $centerY + 1, 'z' => $centerZ],
            ['x' => $centerX - 1, 'y' => $centerY, 'z' => $centerZ], 
            ['x' => $centerX - 1, 'y' => $centerY - 1, 'z' => $centerZ], 
            ['x' => $centerX, 'y' => $centerY - 2, 'z' => $centerZ], 
        ];
        
        foreach ($stairs as $step) {
            $world->setBlockAt($step['x'], $step['y'], $step['z'], VanillaBlocks::COBBLESTONE());
            $world->setBlockAt($step['x'], $step['y'] + 1, $step['z'], VanillaBlocks::AIR());
            $world->setBlockAt($step['x'], $step['y'] + 2, $step['z'], VanillaBlocks::AIR());
        }

        $world->setBlockAt($centerX - 2, $centerY + 3, $centerZ + 1, VanillaBlocks::TORCH());
        $world->setBlockAt($centerX, $centerY - 1, $centerZ + 1, VanillaBlocks::TORCH());
    }

    private function generateStructures(World $world, Position $center, Player $player = null): void {
        $this->portalStructure->generate($world, $center);
        $minionType = "cobblestone";
        $this->minionAreaStructure->generate($world, $center, $player, $minionType);
    }

    private function placeUndergroundChest(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        $chestX = $centerX;
        $chestY = $centerY - 3;
        $chestZ = $centerZ - 1;
        
        $world->setBlockAt($chestX, $chestY, $chestZ, VanillaBlocks::CHEST());
        $this->chestGenerator->scheduleChestFill($world, new Position($chestX, $chestY, $chestZ, $world));
    }

    private function spawnOzzyNPC(Player $player, World $world, Position $center): void {
        try {
            $centerX = (int)$center->getX();
            $centerY = (int)$center->getY();
            $centerZ = (int)$center->getZ();
            $npcPosition = new Position(
                $centerX - 3,
                $centerY + self::SURFACE_LEVEL + 1,
                $centerZ - 3,
                $world
            );
            
            $npcManager = $this->plugin->getNPCManager();
            $success = $npcManager->spawnNPC($player, $npcPosition);
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to spawn NPC on second island for " . $player->getName() . ": " . $e->getMessage());
        }
    }
}