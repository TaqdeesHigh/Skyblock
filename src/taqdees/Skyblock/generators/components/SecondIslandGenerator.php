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
use taqdees\Skyblock\generators\structures\MineshaftStructure;

class SecondIslandGenerator {

    private Main $plugin;
    private PortalStructure $portalStructure;
    private MinionAreaStructure $minionAreaStructure;
    private MineshaftStructure $mineshaftStructure;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->portalStructure = new PortalStructure();
        $this->minionAreaStructure = new MinionAreaStructure();
        $this->mineshaftStructure = new MineshaftStructure($plugin);
    }
    public function generate(World $world, Position $center, Player $player = null): void {
        $secondIslandCenter = $this->generateTerrain($world, $center);
        $this->addCobblestoneDecorations($world, $secondIslandCenter);
        $this->generateStructures($world, $secondIslandCenter);
        if ($player !== null) {
            $this->spawnOzzyNPC($player, $world, $secondIslandCenter);
        }
    }

    private function generateTerrain(World $world, Position $center): Position {
        $x = (int)$center->getX() + 64;
        $y = (int)$center->getY();
        $z = (int)$center->getZ();

        for ($dx = -12; $dx <= 12; $dx++) {
            for ($dz = -12; $dz <= 12; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= 12) {
                    $this->generateStoneLayer($world, $x + $dx, $y, $z + $dz, $distance);
                    $this->generateDirtLayer($world, $x + $dx, $y, $z + $dz, $distance);
                    $this->generateGrassLayer($world, $x + $dx, $y, $z + $dz, $distance);
                }
            }
        }

        return new Position($x, $y, $z, $world);
    }

    private function generateStoneLayer(World $world, int $x, int $y, int $z, float $distance): void {
        $stoneDepth = $distance <= 8 ? 6 : ($distance <= 10 ? 4 : 2);
        for ($dy = -$stoneDepth; $dy <= 0; $dy++) {
            if ($distance <= 11.5 - ($dy * -0.2)) {
                $world->setBlockAt($x, $y + $dy, $z, VanillaBlocks::STONE());
            }
        }
    }

    private function generateDirtLayer(World $world, int $x, int $y, int $z, float $distance): void {
        if ($distance <= 11) {
            $dirtHeight = $distance <= 6 ? 2 : 1;
            for ($dy = 1; $dy <= $dirtHeight; $dy++) {
                $world->setBlockAt($x, $y + $dy, $z, VanillaBlocks::DIRT());
            }
        }
    }

    private function generateGrassLayer(World $world, int $x, int $y, int $z, float $distance): void {
        if ($distance <= 10.5) {
            $grassY = $y + ($distance <= 6 ? 3 : 2);
            $world->setBlockAt($x, $grassY, $z, VanillaBlocks::GRASS());
            
            if ($distance > 4 && mt_rand(1, 10) == 1) {
                $world->setBlockAt($x, $grassY + 1, $z, VanillaBlocks::TALL_GRASS());
            }
        }
    }

    private function addCobblestoneDecorations(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();

        for ($dx = -8; $dx <= 8; $dx++) {
            for ($dz = -8; $dz <= 8; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                if ($distance <= 8 && $distance > 4 && mt_rand(1, 6) == 1) {
                    $grassY = $y + ($distance <= 6 ? 3 : 2);
                    $world->setBlockAt($x + $dx, $grassY, $z + $dz, VanillaBlocks::COBBLESTONE());
                }
            }
        }
    }

    private function generateStructures(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();

        $this->portalStructure->generate($world, $x, $y, $z);
        $this->minionAreaStructure->generate($world, $x, $y, $z);
        $this->mineshaftStructure->generateEntrance($world, $x, $y, $z);
        $this->mineshaftStructure->generateUnderground($world, new Position($x, $y, $z, $world));
    }
    private function spawnOzzyNPC(Player $player, World $world, Position $secondIslandCenter): void {
        try {
            $x = (int)$secondIslandCenter->getX();
            $y = (int)$secondIslandCenter->getY();
            $z = (int)$secondIslandCenter->getZ();
            $grassY = $y + 3;
            $npcPosition = new Position($x + 2, $grassY + 1, $z + 2, $world);
            $npcManager = $this->plugin->getNPCManager();
            $success = $npcManager->spawnNPC($player, $npcPosition);
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to spawn NPC on second island for " . $player->getName() . ": " . $e->getMessage());
        }
    }
}