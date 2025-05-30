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

        $worldName = $islandData["world_name"];
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        
        if ($world === null) {
            if (!Server::getInstance()->getWorldManager()->loadWorld($worldName)) {
                $player->sendMessage("§cYour island world could not be loaded! Please contact an administrator.");
                return false;
            }
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        }
        
        if ($world === null) {
            $player->sendMessage("§cYour island world not found!");
            return false;
        }

        $homePosition = new Position(
            $islandData["home"]["x"],
            $islandData["home"]["y"],
            $islandData["home"]["z"],
            $world
        );

        $player->teleport($homePosition);
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
        if ($currentWorld !== $islandData["world_name"]) {
            $player->sendMessage("§cYou can only set home on your own island!");
            return false;
        }

        $position = $player->getPosition();
        $islandData["home"] = [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "world" => $position->getWorld()->getFolderName()
        ];

        $this->dataManager->updateIsland($player->getName(), $islandData);
        $player->sendMessage("§aHome location set!");
        return true;
    }
}