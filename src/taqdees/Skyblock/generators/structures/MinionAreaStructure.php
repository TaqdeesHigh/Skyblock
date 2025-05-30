<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\block\VanillaBlocks;

class MinionAreaStructure {

    // WILL BE ADDED SOON!

    public function generate(World $world, int $x, int $y, int $z): void {
        $minionX = $x;
        $minionZ = $z - 4;
        $minionY = $y + 3;
        for ($dx = -3; $dx <= 3; $dx++) {
            for ($dz = -3; $dz <= 3; $dz++) {
                $world->setBlockAt($minionX + $dx, $minionY, $minionZ + $dz, VanillaBlocks::GRASS());
            }
        }
        for ($dx = -3; $dx <= 3; $dx++) {
            $world->setBlockAt($minionX + $dx, $minionY, $minionZ, VanillaBlocks::COBBLESTONE());
        }
        for ($dz = -3; $dz <= 3; $dz++) {
            $world->setBlockAt($minionX, $minionY, $minionZ + $dz, VanillaBlocks::COBBLESTONE());
        }
    }
}