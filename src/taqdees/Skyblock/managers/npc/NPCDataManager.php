<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\npc;

use pocketmine\world\Position;
use pocketmine\utils\Config;
use taqdees\Skyblock\Main;

class NPCDataManager {

    private Main $plugin;
    private Config $npcConfig;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->initializeConfig();
    }

    private function initializeConfig(): void {
        $this->npcConfig = new Config($this->plugin->getDataFolder() . "npcs.yml", Config::YAML, [
            "npcs" => []
        ]);
    }

    public function saveNPCData(string $playerName, Position $position, string $name): void {
        $npcs = $this->npcConfig->get("npcs", []);
        $npcs[$playerName] = [
            "name" => $name,
            "position" => [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ]
        ];
        
        $this->npcConfig->set("npcs", $npcs);
        $this->npcConfig->save();
    }

    public function updateNPCName(string $playerName, string $name): void {
        $npcs = $this->npcConfig->get("npcs", []);
        if (isset($npcs[$playerName])) {
            $npcs[$playerName]["name"] = $name;
            $this->npcConfig->set("npcs", $npcs);
            $this->npcConfig->save();
        }
    }

    public function updateNPCLocation(string $playerName, Position $position): void {
        $npcs = $this->npcConfig->get("npcs", []);
        if (isset($npcs[$playerName])) {
            $npcs[$playerName]["position"] = [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ];
            $this->npcConfig->set("npcs", $npcs);
            $this->npcConfig->save();
        }
    }

    public function deleteNPCData(string $playerName): void {
        $npcs = $this->npcConfig->get("npcs", []);
        unset($npcs[$playerName]);
        $this->npcConfig->set("npcs", $npcs);
        $this->npcConfig->save();
    }

    public function getAllNPCData(): array {
        return $this->npcConfig->get("npcs", []);
    }

    public function getNPCData(string $playerName): ?array {
        $npcs = $this->npcConfig->get("npcs", []);
        return $npcs[$playerName] ?? null;
    }
}