<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\Server;
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
            "skyblock_world" => null,
            "player_spawn_position" => null
        ]);
    }

    public function createIsland(string $playerName, Position $position, string $worldName): array {
        $islandId = $this->getNextIslandId();
        $spawnPos = $this->getPlayerSpawnPosition();
        $homePosition = $spawnPos ?? $position;
        
        $islandData = [
            "id" => $islandId,
            "owner" => $playerName,
            "members" => [$playerName],
            "world_name" => $worldName,
            "position" => [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $worldName
            ],
            "home" => [
                "x" => $homePosition->getX(),
                "y" => $homePosition->getY(),
                "z" => $homePosition->getZ(),
                "world" => $homePosition->getWorld()->getFolderName()
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

    public function setPlayerSpawnPosition(Position $position): void {
        $positionData = [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "world" => $position->getWorld()->getFolderName()
        ];
        $this->settingsConfig->set("player_spawn_position", $positionData);
        $this->settingsConfig->save();
    }

    public function getPlayerSpawnPosition(): ?Position {
        $positionData = $this->settingsConfig->get("player_spawn_position", null);
        if (!is_array($positionData)) {
            return null;
        }

        $worldName = $positionData["world"] ?? null;
        if (!is_string($worldName)) {
            return null;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if ($world === null) {
            return null;
        }

        return new Position(
            (float)($positionData["x"] ?? 0),
            (float)($positionData["y"] ?? 0),
            (float)($positionData["z"] ?? 0),
            $world
        );
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