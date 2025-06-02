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
        $this->addTreeBase($world, $treeX, $treeY, $treeZ);
    }

    private function findGrassLevel(World $world, int $x, int $y, int $z): int {
        for ($checkY = $y + 5; $checkY >= $y - 5; $checkY--) {
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
                for ($dy = 0; $dy <= 4; $dy++) {
                    $distance = sqrt($dx * $dx + $dz * $dz + ($dy * 0.4) * ($dy * 0.4));
                    
                    if ($distance <= 3.2 && !($dx == 0 && $dz == 0 && $dy <= 2)) {
                        $chance = $distance <= 1.8 ? 10 : ($distance <= 2.5 ? 8 : 6);
                        if (mt_rand(1, 10) <= $chance) {
                            $world->setBlockAt($x + $dx, $leafY + $dy, $z + $dz, VanillaBlocks::OAK_LEAVES());
                        }
                    }
                }
            }
        }
        $topPositions = [
            [0, 0], [1, 0], [-1, 0], [0, 1], [0, -1],
            [1, 1], [-1, -1], [1, -1], [-1, 1]
        ];
        
        foreach ($topPositions as $pos) {
            if (abs($pos[0]) + abs($pos[1]) <= 1 || mt_rand(1, 3) == 1) {
                $world->setBlockAt($x + $pos[0], $leafY + 4, $z + $pos[1], VanillaBlocks::OAK_LEAVES());
            }
        }
    }

    private function addTreeBase(World $world, int $x, int $y, int $z): void {
        $basePositions = [
            [1, 0], [-1, 0], [0, 1], [0, -1]
        ];
        
        foreach ($basePositions as $pos) {
            if (mt_rand(1, 3) == 1) {
                $block = $world->getBlockAt($x + $pos[0], $y - 1, $z + $pos[1]);
                if ($block->getTypeId() === VanillaBlocks::GRASS()->getTypeId()) {
                    $world->setBlockAt($x + $pos[0], $y - 1, $z + $pos[1], VanillaBlocks::DIRT());
                }
            }
        }
    }
}
