<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\Server;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\DataManager;

class IslandCreationService {

    private Main $plugin;
    private DataManager $dataManager;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function createIsland(Player $player): bool {
        if ($this->dataManager->hasIsland($player->getName())) {
            $player->sendMessage("§cYou already have an island! You can only create one island.");
            return false;
        }

        $playerName = $player->getName();
        $islandWorldName = "island_" . strtolower($playerName);
        
        $worldManager = new \taqdees\Skyblock\world\WorldManager($this->plugin);
        $server = Server::getInstance();
        $serverWorldManager = $server->getWorldManager();
        
        if (!$serverWorldManager->isWorldGenerated($islandWorldName)) {
            $player->sendMessage("§7Creating your personal island world...");
            if (!$worldManager->createVoidWorld($islandWorldName)) {
                $player->sendMessage("§cFailed to create your island world!");
                return false;
            }
        }

        if (!$serverWorldManager->isWorldLoaded($islandWorldName)) {
            if (!$serverWorldManager->loadWorld($islandWorldName)) {
                $player->sendMessage("§cFailed to load your island world!");
                return false;
            }
        }

        $world = $serverWorldManager->getWorldByName($islandWorldName);
        if ($world === null) {
            $player->sendMessage("§cYour island world is not accessible!");
            return false;
        }

        try {
            $islandCenter = new Position(0, 64, 0, $world);
            $this->generateAndWaitForChunks($world, $islandCenter, function() use ($player, $world, $islandCenter, $islandWorldName) {
                $this->finishIslandCreation($player, $world, $islandCenter, $islandWorldName);
            });
            
            return true;
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to create island for " . $player->getName() . ": " . $e->getMessage());
            $player->sendMessage("§cAn error occurred while creating your island. Please try again.");
            return false;
        }
    }

    private function generateAndWaitForChunks(World $world, Position $center, callable $callback): void {
        $chunkX = $center->getFloorX() >> 4;
        $chunkZ = $center->getFloorZ() >> 4;
        for ($x = $chunkX - 1; $x <= $chunkX + 1; $x++) {
            for ($z = $chunkZ - 1; $z <= $chunkZ + 1; $z++) {
                $world->loadChunk($x, $z);
            }
        }
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new class($callback) extends \pocketmine\scheduler\Task {
                private $callback;
                
                public function __construct(callable $callback) {
                    $this->callback = $callback;
                }
                
                public function onRun(): void {
                    ($this->callback)();
                }
            },
            10
        );
    }

    private function finishIslandCreation(Player $player, World $world, Position $islandCenter, string $islandWorldName): void {
        try {
            $islandGenerator = new \taqdees\Skyblock\generators\IslandGenerator($this->plugin);
            if (!$islandGenerator->generateIsland($world, $islandCenter)) {
                $player->sendMessage($this->plugin->getConfigValue('messages.island_creation_failed', "§cFailed to generate your island!"));
                return;
            }
            $islandData = $this->dataManager->createIsland($player->getName(), $islandCenter, $islandWorldName);
            $spawnHeight = $this->plugin->getConfigValue('island.generation.spawn_height', 64);
            $spawnPosition = new Position(0, $spawnHeight + 2, 0, $world);
            $player->teleport($spawnPosition);
            
            $player->sendMessage($this->plugin->getConfigValue('messages.island_created', "§aIsland created successfully! Welcome to your personal island!"));
            $player->sendMessage("§7You now have your own private island world!");
            $player->sendMessage("§7Use /is home to return here anytime.");
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to finish island creation for " . $player->getName() . ": " . $e->getMessage());
            $player->sendMessage($this->plugin->getConfigValue('messages.island_creation_failed', "§cAn error occurred while creating your island. Please try again."));
        }
    }
}