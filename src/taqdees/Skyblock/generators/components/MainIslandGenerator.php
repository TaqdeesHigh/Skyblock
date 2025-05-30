<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\components;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;

class MainIslandGenerator {

    public function generate(World $world, Position $center): void {
        $this->generateTerrain($world, $center);
        $this->addRandomDirtSpots($world, $center);
    }

    private function generateTerrain(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();

        for ($dx = -8; $dx <= 8; $dx++) {
            for ($dz = -8; $dz <= 8; $dz++) {
                $distance = sqrt($dx * $dx + $dz * $dz);
                
                if ($distance <= 8) {
                    $this->generateStoneLayer($world, $x + $dx, $y, $z + $dz, $distance);
                    $this->generateDirtLayer($world, $x + $dx, $y, $z + $dz, $distance);
                    $this->generateGrassLayer($world, $x + $dx, $y, $z + $dz, $distance);
                }
            }
        }
    }

    private function generateStoneLayer(World $world, int $x, int $y, int $z, float $distance): void {
        $stoneDepth = $distance <= 4 ? 4 : ($distance <= 6 ? 3 : 2);
        for ($dy = -$stoneDepth; $dy < 0; $dy++) {
            if ($distance <= 7.5 - ($dy * -0.3)) {
                $world->setBlockAt($x, $y + $dy, $z, VanillaBlocks::STONE());
            }
        }
    }

    private function generateDirtLayer(World $world, int $x, int $y, int $z, float $distance): void {
        $dirtHeight = $distance <= 3 ? 2 : 1;
        for ($dy = 0; $dy < $dirtHeight; $dy++) {
            if ($distance <= 7 - ($dy * 0.5)) {
                $world->setBlockAt($x, $y + $dy, $z, VanillaBlocks::DIRT());
            }
        }
    }

    private function generateGrassLayer(World $world, int $x, int $y, int $z, float $distance): void {
        if ($distance <= 6.5) {
            $dirtHeight = $distance <= 3 ? 2 : 1;
            $grassY = $y + $dirtHeight;
            
            if ($distance <= 2 && mt_rand(1, 4) == 1) {
                $grassY++;
            }
            
            $world->setBlockAt($x, $grassY, $z, VanillaBlocks::GRASS());
            
            if (mt_rand(1, 8) == 1) {
                $world->setBlockAt($x, $grassY + 1, $z, VanillaBlocks::TALL_GRASS());
            }
        }
    }

    private function addRandomDirtSpots(World $world, Position $center): void {
        $x = (int)$center->getX();
        $y = (int)$center->getY();
        $z = (int)$center->getZ();

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