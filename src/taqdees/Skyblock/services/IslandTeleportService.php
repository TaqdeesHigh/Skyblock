<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\Server;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\DataManager;

class IslandTeleportService {

    private Main $plugin;
    private DataManager $dataManager;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function teleportToIsland(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island! Use /is create to make one.");
            return false;
        }
        $worldName = "island_" . strtolower($player->getName());
        $server = Server::getInstance();
        $worldManager = $server->getWorldManager();
        
        if (!$worldManager->isWorldLoaded($worldName)) {
            if (!$worldManager->loadWorld($worldName)) {
                $player->sendMessage("§cFailed to load your island world!");
                return false;
            }
        }

        $world = $worldManager->getWorldByName($worldName);
        if ($world === null) {
            $player->sendMessage("§cYour island world is not accessible!");
            return false;
        }
        
        $homePos = $islandData["home"] ?? null;
        if ($homePos !== null) {
            $position = new Position($homePos["x"], $homePos["y"], $homePos["z"], $world);
        } else {
            $position = new Position(
                $islandData["position"]["x"],
                $islandData["position"]["y"] + 2,
                $islandData["position"]["z"],
                $world
            );
        }

        $player->teleport($position);
        
        $player->setGamemode(\pocketmine\player\GameMode::SURVIVAL());
        
        $player->sendMessage("§aWelcome back to your island!");
        return true;
    }

    public function setHome(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        $currentWorld = $player->getWorld()->getFolderName();
        $expectedWorld = "island_" . strtolower($player->getName());
        
        if ($currentWorld !== $expectedWorld) {
            $player->sendMessage("§cYou can only set home on your own island!");
            return false;
        }

        $position = $player->getPosition();
        $this->dataManager->setIslandHome($player->getName(), $position);
        $player->sendMessage("§aHome position set!");
        return true;
    }
}