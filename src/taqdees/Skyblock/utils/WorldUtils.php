<?php

declare(strict_types=1);

namespace taqdees\Skyblock\utils;

use pocketmine\Server;
use taqdees\Skyblock\Main;

class WorldUtils {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function duplicateWorld(string $sourceWorldName, string $targetWorldName): bool {
        $server = Server::getInstance();
        $worldManager = $server->getWorldManager();
        $dataPath = $server->getDataPath();
        
        $sourceWorldPath = $dataPath . "worlds" . DIRECTORY_SEPARATOR . $sourceWorldName;
        $targetWorldPath = $dataPath . "worlds" . DIRECTORY_SEPARATOR . $targetWorldName;

        if (!is_dir($sourceWorldPath)) {
            $this->plugin->getLogger()->error("Source world directory not found: " . $sourceWorldPath);
            return false;
        }

        if (is_dir($targetWorldPath)) {
            $this->plugin->getLogger()->warning("Target world already exists: " . $targetWorldName);
            return false;
        }

        $wasLoaded = $this->unloadWorldTemporarily($sourceWorldName);
        
        try {
            $this->copyDirectory($sourceWorldPath, $targetWorldPath);
            $this->updateLevelDat($targetWorldPath, $targetWorldName);
            return true;
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to duplicate world: " . $e->getMessage());
            if (is_dir($targetWorldPath)) {
                $this->removeDirectory($targetWorldPath);
            }
            return false;
            
        } finally {
            if ($wasLoaded) {
                $worldManager->loadWorld($sourceWorldName);
            }
        }
    }

    public function deleteWorld(string $worldName): void {
        $worldManager = Server::getInstance()->getWorldManager();
        
        if ($worldManager->isWorldLoaded($worldName)) {
            $world = $worldManager->getWorldByName($worldName);
            if ($world !== null) {
                foreach ($world->getPlayers() as $player) {
                    $defaultWorld = $worldManager->getDefaultWorld();
                    if ($defaultWorld !== null) {
                        $player->teleport($defaultWorld->getSpawnLocation());
                        $player->sendMessage("§7You have been moved from a deleted island world.");
                    }
                }
            }
            
            $worldManager->unloadWorld($world, true);
        }

        $worldPath = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $worldName;
        if (is_dir($worldPath)) {
            $this->removeDirectory($worldPath);
            $this->plugin->getLogger()->info("Deleted island world: " . $worldName);
        }
    }

    private function unloadWorldTemporarily(string $worldName): bool {
        $worldManager = Server::getInstance()->getWorldManager();
        
        if ($worldManager->isWorldLoaded($worldName)) {
            $sourceWorld = $worldManager->getWorldByName($worldName);
            if ($sourceWorld !== null) {
                foreach ($sourceWorld->getPlayers() as $player) {
                    $defaultWorld = $worldManager->getDefaultWorld();
                    if ($defaultWorld !== null) {
                        $player->teleport($defaultWorld->getSpawnLocation());
                        $player->sendMessage("§7Temporarily moved for world duplication...");
                    }
                }
                $worldManager->unloadWorld($sourceWorld, true);
                usleep(500000);
                return true;
            }
        }
        
        return false;
    }

    private function copyDirectory(string $source, string $target): void {
        if (!is_dir($source)) {
            throw new \RuntimeException("Source directory does not exist: " . $source);
        }
        
        if (!mkdir($target, 0755, true) && !is_dir($target)) {
            throw new \RuntimeException("Failed to create target directory: " . $target);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new \RuntimeException("Failed to create directory: " . $targetPath);
                }
            } else {
                if (!copy($item->getPathname(), $targetPath)) {
                    throw new \RuntimeException("Failed to copy file: " . $item->getPathname());
                }
            }
        }
    }

    private function updateLevelDat(string $worldPath, string $worldName): void {
        $levelDatPath = $worldPath . DIRECTORY_SEPARATOR . "level.dat";
        
        if (!file_exists($levelDatPath)) {
            $this->plugin->getLogger()->warning("level.dat not found in world: " . $worldPath);
            return;
        }
        
        try {
            $nbt = file_get_contents($levelDatPath);
            if ($nbt === false) {
                throw new \RuntimeException("Failed to read level.dat");
            }
            $this->plugin->getLogger()->info("World " . $worldName . " copied successfully");
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->warning("Failed to update level.dat for world " . $worldName . ": " . $e->getMessage());
        }
    }

    private function removeDirectory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}