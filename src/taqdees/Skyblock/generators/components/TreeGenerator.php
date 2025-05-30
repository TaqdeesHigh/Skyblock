<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\components;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;

class TreeGenerator {

    public function generate(World $world, Position $center): void {
        $treeX = (int)$center->getX() + 3;
        $treeY = (int)$center->getY() + 3; 
        $treeZ = (int)$center->getZ() - 2;

        $treeY = $this->findGrassLevel($world, $treeX, $treeY, $treeZ);
        
        $this->generateTrunk($world, $treeX, $treeY, $treeZ);
        $this->generateLeaves($world, $treeX, $treeY, $treeZ);
    }

    private function findGrassLevel(World $world, int $x, int $y, int $z): int {
        for ($checkY = $y + 3; $checkY >= $y - 3; $checkY--) {
            $block = $world->getBlockAt($x, $checkY, $z);
            if ($block->getTypeId() === VanillaBlocks::GRASS()->getTypeId()) {
                return $checkY + 1;
            }
        }
        return $y;
    }

    private function generateTrunk(World $world, int $x, int $y, int $z): void {
        for ($i = 0; $i < 6; $i++) {
            $world->setBlockAt($x, $y + $i, $z, VanillaBlocks::OAK_LOG());
        }
    }

    private function generateLeaves(World $world, int $x, int $y, int $z): void {
        $leafY = $y + 3;
        for ($dx = -3; $dx <= 3; $dx++) {
            for ($dz = -3; $dz <= 3; $dz++) {
                for ($dy = 0; $dy <= 3; $dy++) {
                    $distance = sqrt($dx * $dx + $dz * $dz + ($dy * 0.5) * ($dy * 0.5));
                    
                    if ($distance <= 3 && !($dx == 0 && $dz == 0 && $dy <= 2)) {
                        $chance = $distance <= 2 ? 9 : ($distance <= 2.5 ? 7 : 5);
                        if (mt_rand(1, 10) <= $chance) {
                            $world->setBlockAt($x + $dx, $leafY + $dy, $z + $dz, VanillaBlocks::OAK_LEAVES());
                        }
                    }
                }
            }
        }
        $topPositions = [
            [0, 0], [1, 0], [-1, 0], [0, 1], [0, -1]
        ];
        
        foreach ($topPositions as $pos) {
            $world->setBlockAt($x + $pos[0], $leafY + 4, $z + $pos[1], VanillaBlocks::OAK_LEAVES());
        }
    }
}