<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\Server;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\DataManager;

class IslandDeletionService {

    private Main $plugin;
    private DataManager $dataManager;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function deleteIsland(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island to delete!");
            return false;
        }

        $worldName = "island_" . strtolower($player->getName());
        $server = Server::getInstance();
        $worldManager = $server->getWorldManager();
        $world = $worldManager->getWorldByName($worldName);
        if ($world !== null) {
            foreach ($world->getPlayers() as $worldPlayer) {
                $spawnWorld = $server->getWorldManager()->getDefaultWorld();
                $worldPlayer->teleport($spawnWorld->getSpawnLocation());
                $worldPlayer->sendMessage("§7You have been teleported to spawn as the island was deleted.");
            }
            $worldManager->unloadWorld($world);
        }
        $this->dataManager->deleteIsland($player->getName());
        $worldPath = $server->getDataPath() . "worlds/" . $worldName;
        if (is_dir($worldPath)) {
            $this->deleteDirectory($worldPath);
        }

        $player->sendMessage("§aYour island has been deleted successfully!");
        return true;
    }

    private function deleteDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
}