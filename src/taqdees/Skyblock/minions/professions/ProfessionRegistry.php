<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions\professions;

class ProfessionRegistry {
    
    /** @var Profession[] */
    private static array $professions = [];
    
    public static function init(): void {
        self::register(new FarmingProfession());
        self::register(new MiningProfession());
        self::register(new WoodcuttingProfession());
        self::register(new FishingProfession());
    }
    
    public static function register(Profession $profession): void {
        self::$professions[strtolower($profession->getName())] = $profession;
    }
    
    public static function get(string $name): ?Profession {
        return self::$professions[strtolower($name)] ?? null;
    }
    
    public static function getAll(): array {
        return self::$professions;
    }
}