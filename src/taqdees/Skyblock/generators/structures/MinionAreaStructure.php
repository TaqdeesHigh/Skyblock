<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;

class MinionAreaStructure {

    public function generate(World $world, Position $center): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        $minionX = $centerX + 5;
        $minionZ = $centerZ;
        $minionY = $centerY + 2;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($minionX + $dx, $minionY, $minionZ + $dz, VanillaBlocks::COBBLESTONE());
            }
        }
        $corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
        foreach ($corners as $corner) {
            $world->setBlockAt($minionX + $corner[0], $minionY, $minionZ + $corner[1], VanillaBlocks::STONE_BRICKS());
        }
    }
}