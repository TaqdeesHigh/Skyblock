<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;

class PortalStructure {

    public function generate(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();

        $portalX = $centerX - 5;
        $portalZ = $centerZ;
        $portalY = $centerY + 2; 
        
        $this->createNetherPortalFrame($world, $portalX, $portalY, $portalZ);
    }

    private function createNetherPortalFrame(World $world, int $x, int $y, int $z): void {
        for ($dz = 0; $dz <= 3; $dz++) {
            $world->setBlockAt($x, $y, $z + $dz, VanillaBlocks::POLISHED_ANDESITE());
        }
        for ($dz = 0; $dz <= 3; $dz++) {
            $world->setBlockAt($x, $y + 4, $z + $dz, VanillaBlocks::POLISHED_ANDESITE());
        }
        for ($dy = 1; $dy <= 3; $dy++) {
            $world->setBlockAt($x, $y + $dy, $z, VanillaBlocks::POLISHED_ANDESITE());
            $world->setBlockAt($x, $y + $dy, $z + 3, VanillaBlocks::POLISHED_ANDESITE());
        }
        for ($dz = 1; $dz <= 2; $dz++) {
            for ($dy = 1; $dy <= 3; $dy++) {
                $world->setBlockAt($x, $y + $dy, $z + $dz, VanillaBlocks::AIR());
            }
        }
        for ($dz = -1; $dz <= 4; $dz++) {
            $world->setBlockAt($x, $y - 1, $z + $dz, VanillaBlocks::POLISHED_ANDESITE());
        }
    }
}