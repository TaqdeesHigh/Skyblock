<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\world\Position;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\services\IslandCreationService;
use taqdees\Skyblock\services\IslandTeleportService;
use taqdees\Skyblock\services\IslandMemberService;
use taqdees\Skyblock\services\IslandDeletionService;

class IslandManager {

    private Main $plugin;
    private DataManager $dataManager;
    private IslandCreationService $creationService;
    private IslandTeleportService $teleportService;
    private IslandMemberService $memberService;
    private IslandDeletionService $deletionService;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
        $this->initializeServices();
    }

    private function initializeServices(): void {
        $this->creationService = new IslandCreationService($this->plugin, $this->dataManager);
        $this->teleportService = new IslandTeleportService($this->plugin, $this->dataManager);
        $this->memberService = new IslandMemberService($this->plugin, $this->dataManager);
        $this->deletionService = new IslandDeletionService($this->plugin, $this->dataManager);
    }

    public function createIsland(Player $player): bool {
        return $this->creationService->createIsland($player);
    }

    public function teleportToIsland(Player $player): bool {
        return $this->teleportService->teleportToIsland($player);
    }

    public function setHome(Player $player): bool {
        return $this->teleportService->setHome($player);
    }

    public function inviteMember(Player $player, string $memberName): bool {
        return $this->memberService->inviteMember($player, $memberName);
    }

    public function kickMember(Player $player, string $memberName): bool {
        return $this->memberService->kickMember($player, $memberName);
    }

    public function leaveIsland(Player $player): bool {
        return $this->memberService->leaveIsland($player);
    }

    public function getMembers(Player $player): ?array {
        return $this->memberService->getMembers($player);
    }

    public function deleteIsland(Player $player): bool {
        return $this->deletionService->deleteIsland($player);
    }

    public function isOnIsland(Player $player, Position $position): bool {
        $currentWorldName = $position->getWorld()->getFolderName();
        
        // Check if player owns this island
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData !== null && $islandData["world_name"] === $currentWorldName) {
            return true;
        }

        // Check if player is a member of this island
        $allIslands = $this->dataManager->getAllIslands();
        foreach ($allIslands as $owner => $data) {
            if (in_array($player->getName(), $data["members"]) && $data["world_name"] === $currentWorldName) {
                return true;
            }
        }
        
        return false;
    }
}