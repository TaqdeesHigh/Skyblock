<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\CobblestoneMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\CoalMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\IronMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\GoldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\DiamondMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\LapisMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\EmeraldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\RedstoneMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\WheatMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\CarrotMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PotatoMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PumpkinMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\MelonMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\OakMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\SpruceMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\BirchMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\AcaciaMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\DarkOakMinion;

class MinionRegistry {

    private static array $registrations = [];

    private static array $minionClasses = [
        CobblestoneMinion::class,
        CoalMinion::class,
        IronMinion::class,
        GoldMinion::class,
        DiamondMinion::class,
        LapisMinion::class,
        EmeraldMinion::class,
        RedstoneMinion::class,
        WheatMinion::class,
        CarrotMinion::class,
        PotatoMinion::class,
        PumpkinMinion::class,
        MelonMinion::class,
        OakMinion::class,
        SpruceMinion::class,
        BirchMinion::class,
        AcaciaMinion::class,
        DarkOakMinion::class,
    ];

    public static function register(string $class, string $type, array $identifiers): void {
        self::$registrations[$type] = [
            'class'       => $class,
            'identifiers' => $identifiers,
        ];
    }

    private static function preloadClasses(): void {
        foreach (self::$minionClasses as $class) {
            class_exists($class);
        }
    }

    public static function init(Main $plugin): void {
        self::preloadClasses();

        $factory = EntityFactory::getInstance();

        foreach (self::$registrations as $type => $entry) {
            $class       = $entry['class'];
            $identifiers = $entry['identifiers'];

            $factory->register(
                $class,
                function (World $world, CompoundTag $nbt) use ($plugin, $class, $type): BaseMinion {
                    return new $class($plugin, EntityDataHelper::parseLocation($nbt, $world), $type, null, $nbt);
                },
                $identifiers
            );
        }
    }

    public static function getRegisteredTypes(): array {
        return array_keys(self::$registrations);
    }

    public static function getClass(string $type): ?string {
        return self::$registrations[$type]['class'] ?? null;
    }
}