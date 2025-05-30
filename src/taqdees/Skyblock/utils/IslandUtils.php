<?php

declare(strict_types=1);

namespace taqdees\Skyblock\utils;

use pocketmine\world\Position;

class IslandUtils {

    public static function calculateIslandDistance(Position $pos1, Position $pos2): float {
        $dx = $pos1->getX() - $pos2->getX();
        $dz = $pos1->getZ() - $pos2->getZ();
        return sqrt($dx * $dx + $dz * $dz);
    }

    public static function isWithinIslandBounds(Position $playerPos, Position $islandCenter, int $radius = 50): bool {
        $distance = self::calculateIslandDistance($playerPos, $islandCenter);
        return $distance <= $radius;
    }

    public static function getNextIslandPosition(int $islandId, int $spacing = 1000): array {
        $islandsPerRow = 10;
        
        $row = intval(($islandId - 1) / $islandsPerRow);
        $col = ($islandId - 1) % $islandsPerRow;
        
        return [
            'x' => $col * $spacing,
            'y' => 64,
            'z' => $row * $spacing
        ];
    }
}