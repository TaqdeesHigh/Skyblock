<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\Server;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\DataManager;
use taqdees\Skyblock\utils\WorldUtils;

class IslandCreationService {

    private Main $plugin;
    private DataManager $dataManager;
    private WorldUtils $worldUtils;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
        $this->worldUtils = new WorldUtils($plugin);
    }

    public function createIsland(Player $player): bool {
        if ($this->dataManager->hasIsland($player->getName())) {
            $player->sendMessage("§cYou already have an island! You can only create one island.");
            return false;
        }

        $templateWorld = $this->dataManager->getSkyblockWorld();
        if ($templateWorld === null) {
            $player->sendMessage("§cSkyblock template world is not set up yet!");
            return false;
        }

        $sourceWorld = Server::getInstance()->getWorldManager()->getWorldByName($templateWorld);
        if ($sourceWorld === null) {
            $player->sendMessage("§cSkyblock template world not found!");
            return false;
        }

        $playerWorldName = "island_" . strtolower($player->getName()) . "_" . time();
        
        try {
            if (!$this->worldUtils->duplicateWorld($templateWorld, $playerWorldName)) {
                $player->sendMessage("§cFailed to create your island world!");
                return false;
            }

            $worldManager = Server::getInstance()->getWorldManager();
            if (!$worldManager->loadWorld($playerWorldName)) {
                $player->sendMessage("§cFailed to load your island world!");
                return false;
            }

            $playerWorld = $worldManager->getWorldByName($playerWorldName);
            if ($playerWorld === null) {
                $player->sendMessage("§cFailed to access your island world!");
                return false;
            }

            $spawnPosition = new Position(0, 64, 0, $playerWorld);
            $islandData = $this->dataManager->createIsland($player->getName(), $spawnPosition, $playerWorldName);
            
            $homePosition = new Position(
                $islandData["home"]["x"],
                $islandData["home"]["y"],
                $islandData["home"]["z"],
                $playerWorld
            );

            $player->teleport($homePosition);
            
            $player->sendMessage("§aIsland created successfully! Welcome to your personal island!");
            $player->sendMessage("§7You now have your own private world to build in!");
            $player->sendMessage("§7Use /is home to return here anytime.");
            
            return true;
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to create island for " . $player->getName() . ": " . $e->getMessage());
            $player->sendMessage("§cAn error occurred while creating your island. Please try again.");
            return false;
        }
    }
}