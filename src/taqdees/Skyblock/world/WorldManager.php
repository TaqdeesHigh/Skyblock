<?php

declare(strict_types=1);

namespace taqdees\Skyblock\world;

use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\math\Vector3;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\generators\VoidWorldGenerator;

class WorldManager {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function createVoidWorld(string $worldName): bool {
        $server = Server::getInstance();
        $worldManager = $server->getWorldManager();

        if ($worldManager->isWorldGenerated($worldName)) {
            $this->plugin->getLogger()->warning("World '$worldName' already exists!");
            return false;
        }

        try {
            VoidWorldGenerator::register();
            $options = new WorldCreationOptions();
            $options->setGeneratorClass(VoidWorldGenerator::class);
            $options->setSpawnPosition(new Vector3(0, 64, 0));
            
            $worldManager->generateWorld($worldName, $options);
            
            if (!$worldManager->loadWorld($worldName)) {
                $this->plugin->getLogger()->error("Failed to load void world: $worldName");
                return false;
            }

            $this->plugin->getLogger()->info("Created void world: $worldName");
            return true;

        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to create void world '$worldName': " . $e->getMessage());
            return false;
        }
    }

    public function getIslandSpawnPosition(int $islandId): array {
        $spacing = 1000;
        $islandsPerRow = 10;
        
        $row = intval(($islandId - 1) / $islandsPerRow);
        $col = ($islandId - 1) % $islandsPerRow;
        
        $x = $col * $spacing;
        $z = $row * $spacing;
        $y = 64;
        
        return ['x' => $x, 'y' => $y, 'z' => $z];
    }
}