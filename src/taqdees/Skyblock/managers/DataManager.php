<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\utils\Config;
use pocketmine\world\Position;
use taqdees\Skyblock\Main;

class DataManager {

    private Main $plugin;
    private Config $islandsConfig;
    private Config $settingsConfig;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->initializeConfigs();
    }

    private function initializeConfigs(): void {
        $this->islandsConfig = new Config($this->plugin->getDataFolder() . "islands.yml", Config::YAML, [
            "islands" => [],
            "next_island_id" => 1
        ]);
        
        $this->settingsConfig = new Config($this->plugin->getDataFolder() . "settings.yml", Config::YAML, [
            "skyblock_world" => null
        ]);
    }

    public function createIsland(string $playerName, Position $position): array {
        $islandId = $this->getNextIslandId();
        $islandData = [
            "id" => $islandId,
            "owner" => $playerName,
            "members" => [$playerName],
            "position" => [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ],
            "home" => [
                "x" => $position->getX(),
                "y" => $position->getY() + 1,
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ],
            "created" => time()
        ];

        $islands = $this->islandsConfig->get("islands", []);
        $islands[$playerName] = $islandData;
        $this->islandsConfig->set("islands", $islands);
        $this->islandsConfig->set("next_island_id", $islandId + 1);
        $this->islandsConfig->save();

        return $islandData;
    }

    public function getIsland(string $playerName): ?array {
        $islands = $this->islandsConfig->get("islands", []);
        return $islands[$playerName] ?? null;
    }

    public function deleteIsland(string $playerName): bool {
        $islands = $this->islandsConfig->get("islands", []);
        if (!isset($islands[$playerName])) {
            return false;
        }

        unset($islands[$playerName]);
        $this->islandsConfig->set("islands", $islands);
        $this->islandsConfig->save();
        return true;
    }

    public function updateIsland(string $playerName, array $data): void {
        $islands = $this->islandsConfig->get("islands", []);
        $islands[$playerName] = $data;
        $this->islandsConfig->set("islands", $islands);
        $this->islandsConfig->save();
    }

    public function setSkyblockWorld(string $worldName): void {
        $this->settingsConfig->set("skyblock_world", $worldName);
        $this->settingsConfig->save();
    }

    public function getSkyblockWorld(): ?string {
        $world = $this->settingsConfig->get("skyblock_world", null);
        if ($world === null || $world === false || !is_string($world)) {
            return null;
        }
        return $world;
    }

    private function getNextIslandId(): int {
        $id = $this->islandsConfig->get("next_island_id", 1);
        return is_int($id) ? $id : 1;
    }

    public function getAllIslands(): array {
        $islands = $this->islandsConfig->get("islands", []);
        return is_array($islands) ? $islands : [];
    }

    public function hasIsland(string $playerName): bool {
        $islands = $this->islandsConfig->get("islands", []);
        return is_array($islands) && isset($islands[$playerName]);
    }
}