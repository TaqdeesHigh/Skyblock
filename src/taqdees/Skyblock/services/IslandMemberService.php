<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\Server;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\DataManager;

class IslandMemberService {

    private Main $plugin;
    private DataManager $dataManager;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function inviteMember(Player $player, string $memberName): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] !== $player->getName()) {
            $player->sendMessage("§cOnly the island owner can invite members!");
            return false;
        }

        if (in_array($memberName, $islandData["members"])) {
            $player->sendMessage("§c$memberName is already a member of your island!");
            return false;
        }

        $islandData["members"][] = $memberName;
        $this->dataManager->updateIsland($player->getName(), $islandData);
        $player->sendMessage("§a$memberName has been invited to your island!");
        
        $invitedPlayer = Server::getInstance()->getPlayerExact($memberName);
        if ($invitedPlayer !== null) {
            $invitedPlayer->sendMessage("§aYou have been invited to " . $player->getName() . "'s island!");
            $invitedPlayer->sendMessage("§7You can now visit their island and build there.");
        }
        
        return true;
    }

    public function kickMember(Player $player, string $memberName): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] !== $player->getName()) {
            $player->sendMessage("§cOnly the island owner can kick members!");
            return false;
        }

        $memberIndex = array_search($memberName, $islandData["members"]);
        if ($memberIndex === false) {
            $player->sendMessage("§c$memberName is not a member of your island!");
            return false;
        }

        if ($memberName === $player->getName()) {
            $player->sendMessage("§cYou cannot kick yourself!");
            return false;
        }

        unset($islandData["members"][$memberIndex]);
        $islandData["members"] = array_values($islandData["members"]);
        
        $this->dataManager->updateIsland($player->getName(), $islandData);
        $player->sendMessage("§a$memberName has been kicked from your island!");
        
        $kickedPlayer = Server::getInstance()->getPlayerExact($memberName);
        if ($kickedPlayer !== null) {
            $kickedPlayer->sendMessage("§cYou have been kicked from " . $player->getName() . "'s island!");
            if ($kickedPlayer->getWorld()->getFolderName() === $islandData["world_name"]) {
                $this->teleportToSpawn($kickedPlayer);
            }
        }
        
        return true;
    }

    public function leaveIsland(Player $player): bool {
        $allIslands = $this->dataManager->getAllIslands();
        $foundIsland = null;
        $ownerName = null;
        
        foreach ($allIslands as $owner => $islandData) {
            if (in_array($player->getName(), $islandData["members"]) && $islandData["owner"] !== $player->getName()) {
                $foundIsland = $islandData;
                $ownerName = $owner;
                break;
            }
        }
        
        if ($foundIsland === null) {
            $player->sendMessage("§cYou are not a member of any island!");
            return false;
        }

        $memberIndex = array_search($player->getName(), $foundIsland["members"]);
        if ($memberIndex !== false) {
            unset($foundIsland["members"][$memberIndex]);
            $foundIsland["members"] = array_values($foundIsland["members"]);
            $this->dataManager->updateIsland($ownerName, $foundIsland);
        }
        
        $this->teleportToSpawn($player);
        $player->sendMessage("§aYou have left the island and been teleported to spawn!");
        
        return true;
    }

    public function getMembers(Player $player): ?array {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return null;
        }

        return $islandData["members"];
    }

    private function teleportToSpawn(Player $player): void {
        $defaultWorld = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld !== null) {
            $player->teleport($defaultWorld->getSpawnLocation());
            $player->sendMessage("§7You have been teleported to spawn.");
        }
    }
}