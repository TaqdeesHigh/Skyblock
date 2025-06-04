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
    private const ISLAND_RADIUS = 9;
    private const STONE_DEPTH = 5;
    private const DIRT_HEIGHT = 2;
    private const SURFACE_LEVEL = 3;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->portalStructure = new PortalStructure();
        $this->minionAreaStructure = new MinionAreaStructure($plugin);
        $this->chestGenerator = new ChestGenerator($plugin);
    }

    public function generate(World $world, Position $center, Player $player = null): void {
        $islandCenter = new Position(
            (int)$center->getX() + 64,
            (int)$center->getY(),
            (int)$center->getZ(),
            $world
        );
        
        $this->generateTerrain($world, $islandCenter);
        $this->addNaturalDecorations($world, $islandCenter);
        $this->generateStructures($world, $islandCenter, $player);
        $this->chestGenerator->placeChest($world, $islandCenter);
        
        if ($player !== null) {
            $this->spawnOzzyNPC($player, $world, $islandCenter);
        }
    }

    private function generateTerrain(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        for ($dx = -self::ISLAND_RADIUS; $dx <= self::ISLAND_RADIUS; $dx++) {
            for ($dz = -self::ISLAND_RADIUS; $dz <= self::ISLAND_RADIUS; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= self::ISLAND_RADIUS) {
                    $x = $centerX + $dx;
                    $z = $centerZ + $dz;
                    $this->generateStoneFoundation($world, $x, $centerY, $z, $distance);
                    $this->generateDirtLayer($world, $x, $centerY, $z, $distance);
                    $this->generateSurfaceLayer($world, $x, $centerY, $z, $distance);
                }
            }
        }
    }

    private function generateStoneFoundation(World $world, int $x, int $baseY, int $z, float $distance): void {
        $depth = (int)(self::STONE_DEPTH * (1 - ($distance / self::ISLAND_RADIUS) * 0.4));
        
        for ($dy = -$depth; $dy <= 0; $dy++) {
            $world->setBlockAt($x, $baseY + $dy, $z, VanillaBlocks::STONE());
        }
    }

    private function generateDirtLayer(World $world, int $x, int $baseY, int $z, float $distance): void {
        $maxHeight = (int)(self::DIRT_HEIGHT * (1 - ($distance / self::ISLAND_RADIUS) * 0.3));
        for ($dy = 1; $dy <= $maxHeight; $dy++) {
            $world->setBlockAt($x, $baseY + $dy, $z, VanillaBlocks::DIRT());
        }
    }

    private function generateSurfaceLayer(World $world, int $x, int $baseY, int $z, float $distance): void {
        $surfaceHeight = $this->getSurfaceHeight($distance);
        $surfaceY = $baseY + $surfaceHeight;
        $world->setBlockAt($x, $surfaceY, $z, VanillaBlocks::GRASS());
        if ($distance > 2 && $distance < 7 && mt_rand(1, 12) == 1) {
            $world->setBlockAt($x, $surfaceY + 1, $z, VanillaBlocks::TALL_GRASS());
        }
    }

    private function getSurfaceHeight(float $distance): int {
        if ($distance <= 2.5) {
            return self::SURFACE_LEVEL;
        } elseif ($distance <= 5) {
            return self::SURFACE_LEVEL - 1;
        } else {
            return self::SURFACE_LEVEL - 2;
        }
    }

    private function addNaturalDecorations(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        for ($dx = -6; $dx <= 6; $dx++) {
            for ($dz = -6; $dz <= 6; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= 6 && $distance > 1.5 && mt_rand(1, 18) == 1) {
                    $surfaceHeight = $this->getSurfaceHeight($distance);
                    $surfaceY = $centerY + $surfaceHeight;
                    $world->setBlockAt($centerX + $dx, $surfaceY, $centerZ + $dz, VanillaBlocks::COBBLESTONE());
                }
            }
        }
    }

    private function generateStructures(World $world, Position $center, Player $player = null): void {
        $this->portalStructure->generate($world, $center);
        $minionType = "cobblestone";
        
        $this->minionAreaStructure->generate($world, $center, $player, $minionType);
    }

    private function spawnOzzyNPC(Player $player, World $world, Position $center): void {
        try {
            $centerX = (int)$center->getX();
            $centerY = (int)$center->getY();
            $centerZ = (int)$center->getZ();
            $npcPosition = new Position(
                $centerX - 6,
                $centerY + self::SURFACE_LEVEL - 1,
                $centerZ - 6,
                $world
            );
            
            $npcManager = $this->plugin->getNPCManager();
            $success = $npcManager->spawnNPC($player, $npcPosition);
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to spawn NPC on second island for " . $player->getName() . ": " . $e->getMessage());
        }
    }
}