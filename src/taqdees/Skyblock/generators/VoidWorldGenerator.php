<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators;

use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\format\Chunk;

class VoidWorldGenerator extends Generator {

    private static bool $registered = false;

    public static function register(): void {
        if (!self::$registered) {
            GeneratorManager::getInstance()->addGenerator(self::class, "skyblock_void", fn() => null);
            self::$registered = true;
        }
    }

    public function __construct(int $seed, string $preset) {
        parent::__construct($seed, $preset);
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if ($chunk === null) {
            return;
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void {}
}