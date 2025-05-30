<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\block\VanillaBlocks;

class PortalStructure {

    // WILL BE ADDED WHEN I ADD THE ACTUAL SKYBLOCK HUB FEATURE!

    public function generate(World $world, int $x, int $y, int $z): void {
        $portalX = $x;
        $portalZ = $z + 4;
        $portalY = $y + 3;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($portalX + $dx, $portalY, $portalZ + $dz, VanillaBlocks::STONE_BRICKS());
            }
        }
        $corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
        foreach ($corners as $corner) {
            for ($dy = 1; $dy <= 3; $dy++) {
                $world->setBlockAt($portalX + $corner[0], $portalY + $dy, $portalZ + $corner[1], VanillaBlocks::STONE_BRICKS());
            }
        }
    }
}