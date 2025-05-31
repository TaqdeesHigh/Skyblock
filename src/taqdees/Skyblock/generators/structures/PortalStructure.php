<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\block\VanillaBlocks;

class PortalStructure {

    public function generate(World $world, int $x, int $y, int $z): void {
        $portalX = $x;
        $portalZ = $z + 13;
        $portalY = $y + 3;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($portalX + $dx, $portalY, $portalZ + $dz, VanillaBlocks::COBBLESTONE());
            }
        }
        $corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
        foreach ($corners as $corner) {
            for ($dy = 1; $dy <= 3; $dy++) {
                $world->setBlockAt($portalX + $corner[0], $portalY + $dy, $portalZ + $corner[1], VanillaBlocks::COBBLESTONE());
            }
        }
        for ($dx = -2; $dx <= 2; $dx++) {
            $world->setBlockAt($portalX + $dx, $portalY + 3, $portalZ - 2, VanillaBlocks::COBBLESTONE());
            $world->setBlockAt($portalX + $dx, $portalY + 3, $portalZ + 2, VanillaBlocks::COBBLESTONE());
        }
        for ($dz = -1; $dz <= 1; $dz++) {
            $world->setBlockAt($portalX - 2, $portalY + 3, $portalZ + $dz, VanillaBlocks::COBBLESTONE());
            $world->setBlockAt($portalX + 2, $portalY + 3, $portalZ + $dz, VanillaBlocks::COBBLESTONE());
        }
    }
}