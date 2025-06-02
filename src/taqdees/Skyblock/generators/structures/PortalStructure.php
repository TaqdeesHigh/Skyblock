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
        $portalX = $centerX;
        $portalZ = $centerZ + 7;
        $portalY = $centerY + 1;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($portalX + $dx, $portalY, $portalZ + $dz, VanillaBlocks::STONE_BRICKS());
            }
        }
        $corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
        foreach ($corners as $corner) {
            for ($dy = 1; $dy <= 4; $dy++) {
                $block = $dy == 4 ? VanillaBlocks::CHISELED_STONE_BRICKS() : VanillaBlocks::STONE_BRICKS();
                $world->setBlockAt($portalX + $corner[0], $portalY + $dy, $portalZ + $corner[1], $block);
            }
        }
        for ($dx = -2; $dx <= 2; $dx++) {
            $world->setBlockAt($portalX + $dx, $portalY + 4, $portalZ - 2, VanillaBlocks::STONE_BRICKS());
            $world->setBlockAt($portalX + $dx, $portalY + 4, $portalZ + 2, VanillaBlocks::STONE_BRICKS());
        }
        for ($dz = -1; $dz <= 1; $dz++) {
            $world->setBlockAt($portalX - 2, $portalY + 4, $portalZ + $dz, VanillaBlocks::STONE_BRICKS());
            $world->setBlockAt($portalX + 2, $portalY + 4, $portalZ + $dz, VanillaBlocks::STONE_BRICKS());
        }
        $world->setBlockAt($portalX, $portalY + 5, $portalZ, VanillaBlocks::CHISELED_STONE_BRICKS());
    }
}