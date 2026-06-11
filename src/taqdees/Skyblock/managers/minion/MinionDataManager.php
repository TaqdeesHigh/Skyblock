<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\utils\Config;
use pocketmine\world\Position;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\CobblestoneMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\CoalMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\IronMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\GoldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\DiamondMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\LapisMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\EmeraldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\RedstoneMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\WheatMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\CarrotMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PotatoMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PumpkinMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\MelonMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\OakMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\SpruceMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\BirchMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\DarkOakMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\AcaciaMinion;

class MinionDataManager {

    private Main $plugin;
    private Config $minionConfig;
    private array $playerMinions = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->initializeConfig();
    }

    private function initializeConfig(): void {
        $this->minionConfig = new Config($this->plugin->getDataFolder() . "minions.yml", Config::YAML, [
            "minions" => []
        ]);
    }

    public function createMinionByType(string $type, \pocketmine\entity\Location $location): ?BaseMinion {
        $class = \taqdees\Skyblock\minions\MinionRegistry::getClass(strtolower($type));
        if ($class === null) {
            return null;
        }
        return new $class($this->plugin, $location, strtolower($type));
    }
    
    public function saveMinionData(string $playerName, Position $position, string $type, int $level): void {
        $minions = $this->minionConfig->get("minions", []);
        if (!isset($minions[$playerName])) {
            $minions[$playerName] = [];
        }

        $minions[$playerName][] = [
            "type" => $type,
            "level" => $level,
            "position" => [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ]
        ];

        $this->minionConfig->set("minions", $minions);
        $this->minionConfig->save();
    }

    public function loadMinions(): void {
        $minions = $this->minionConfig->get("minions", []);
        
        foreach ($minions as $playerName => $playerMinions) {
            $this->playerMinions[$playerName] = [];
            
            foreach ($playerMinions as $minionData) {
                $worldName = $minionData["position"]["world"];
                $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
                
                if ($world === null) continue;
                
                $location = new \pocketmine\entity\Location(
                    $minionData["position"]["x"],
                    $minionData["position"]["y"],
                    $minionData["position"]["z"],
                    $world,
                    0,
                    0
                );
                
                $minion = $this->createMinionByType($minionData["type"], $location);
                if ($minion !== null) {
                    $minion->setLevel($minionData["level"]);
                    $minion->spawnToAll();
                    $this->playerMinions[$playerName][] = $minion;
                }
            }
        }
    }

    public function addPlayerMinion(string $playerName, BaseMinion $minion): void {
        if (!isset($this->playerMinions[$playerName])) {
            $this->playerMinions[$playerName] = [];
        }
        $this->playerMinions[$playerName][] = $minion;
    }

    public function getPlayerMinions(string $playerName): array {
        return $this->playerMinions[$playerName] ?? [];
    }

    public function getPlayerMinionCount(string $playerName): int {
        return count($this->getPlayerMinions($playerName));
    }

    public function removeAllPlayerMinions(string $playerName): void {
        if (isset($this->playerMinions[$playerName])) {
            foreach ($this->playerMinions[$playerName] as $minion) {
                $minion->flagForDespawn();
            }
            unset($this->playerMinions[$playerName]);
        }
    }

    public function removeMinionFromData(string $playerName, BaseMinion $minion): void {
        if (isset($this->playerMinions[$playerName])) {
            $key = array_search($minion, $this->playerMinions[$playerName], true);
            if ($key !== false) {
                unset($this->playerMinions[$playerName][$key]);
                $this->playerMinions[$playerName] = array_values($this->playerMinions[$playerName]);
            }
        }
        
        $minions = $this->minionConfig->get("minions", []);
        if (isset($minions[$playerName])) {
            $minionPos = $minion->getPosition();
            foreach ($minions[$playerName] as $key => $minionData) {
                $savedPos = $minionData["position"];
                if (abs($savedPos["x"] - $minionPos->x) < 0.1 && 
                    abs($savedPos["y"] - $minionPos->y) < 0.1 && 
                    abs($savedPos["z"] - $minionPos->z) < 0.1) {
                    unset($minions[$playerName][$key]);
                    break;
                }
            }
            $minions[$playerName] = array_values($minions[$playerName]);
            $this->minionConfig->set("minions", $minions);
            $this->minionConfig->save();
        }
    }
}