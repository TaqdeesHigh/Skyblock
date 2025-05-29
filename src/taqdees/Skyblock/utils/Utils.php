<?php

declare(strict_types=1);

namespace taqdees\Skyblock\utils;

use pocketmine\world\Position;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;

class Utils {

    public static function positionToArray(Position $position): array {
        return [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "world" => $position->getWorld()->getFolderName()
        ];
    }

    public static function serializeItem(Item $item): array {
        return [
            "id" => $item->getTypeId(),
            "meta" => $item->getMeta(),
            "count" => $item->getCount(),
            "nbt" => $item->getNamedTag()->toString()
        ];
    }

    public static function formatTime(int $timestamp): string {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return $diff . " seconds ago";
        } elseif ($diff < 3600) {
            return floor($diff / 60) . " minutes ago";
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . " hours ago";
        } else {
            return floor($diff / 86400) . " days ago";
        }
    }

    public static function isValidPlayerName(string $name): bool {
        return preg_match('/^[a-zA-Z0-9_]{3,16}$/', $name);
    }
}