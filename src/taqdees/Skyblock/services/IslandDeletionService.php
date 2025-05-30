<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\Server;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\DataManager;
use taqdees\Skyblock\utils\WorldUtils;

class IslandDeletionService {

    private Main $plugin;
    private DataManager $dataManager;
    private WorldUtils $worldUtils;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
        $this->worldUtils = new WorldUtils($plugin);
    }

    public function deleteIsland(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] !== $player->getName()) {
            $player->sendMessage("§cOnly the island owner can delete the island!");
            return false;
        }

        $worldName = $islandData["world_name"];
        $this->dataManager->deleteIsland($player->getName());
        $this->worldUtils->deleteWorld($worldName);
        
        $defaultWorld = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld !== null) {
            $player->teleport($defaultWorld->getSpawnLocation());
            $player->sendMessage("§aYour island has been deleted and you've been teleported to spawn!");
        } else {
            $player->sendMessage("§aYour island has been deleted!");
        }
        
        return true;
    }
}