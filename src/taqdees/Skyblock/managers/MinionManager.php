<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\entities\MinionTypes\CobblestoneMinion;
use taqdees\Skyblock\entities\MinionTypes\WheatMinion;

class MinionManager {

    private Main $plugin;
    private Config $minionConfig;
    /** @var array<string, array<BaseMinion>> */
    private array $playerMinions = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->initializeConfig();
        $this->loadMinions();
    }

    private function initializeConfig(): void {
        $this->minionConfig = new Config($this->plugin->getDataFolder() . "minions.yml", Config::YAML, [
            "minions" => []
        ]);
    }

    public function createMinionEgg(string $minionType): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§6" . ucfirst($minionType) . " Minion Egg");
        $egg->setLore([
            "§7Right-click to spawn a " . $minionType . " minion",
            "§7Automatically works for you!"
        ]);
        $egg->getNamedTag()->setString("minionType", $minionType);
        return $egg;
    }

    public function spawnMinion(Player $player, Position $position, string $minionType): bool {
        if (!isset($this->playerMinions[$player->getName()])) {
            $this->playerMinions[$player->getName()] = [];
        }
        if (count($this->playerMinions[$player->getName()]) >= 5) {
            $player->sendMessage("§cYou have reached the maximum number of minions (5)!");
            return false;
        }

        $location = new \pocketmine\entity\Location(
            $position->getX(),
            $position->getY(),
            $position->getZ(),
            $position->getWorld(),
            0,
            0
        );

        $minion = $this->createMinionByType($minionType, $location);
        if ($minion === null) {
            $player->sendMessage("§cInvalid minion type: " . $minionType);
            return false;
        }

        $minion->spawnToAll();
        $this->playerMinions[$player->getName()][] = $minion;
        $this->saveMinionData($player->getName(), $position, $minionType, 1);
        
        $player->sendMessage("§a" . ucfirst($minionType) . " minion has been spawned!");
        return true;
    }

    private function createMinionByType(string $type, \pocketmine\entity\Location $location): ?BaseMinion {
        switch (strtolower($type)) {
            case "cobblestone":
                return new CobblestoneMinion($this->plugin, $location, "cobblestone");
            case "wheat":
                return new WheatMinion($this->plugin, $location, "wheat");
            default:
                return null;
        }
    }

    public function openMinionMenu(Player $player, BaseMinion $minion): void {
        $player->sendMessage("§6=== " . $minion->getDisplayName() . " ===");
        $player->sendMessage("§7Type: §e" . $minion->getMinionType());
        $player->sendMessage("§7Level: §a" . $minion->getLevel() . "/" . $minion->maxLevel);
        $player->sendMessage("§7Right-click to upgrade (if you have resources)");
    }

    public function upgradeMinion(Player $player, BaseMinion $minion): bool {
        if ($minion->getLevel() >= $minion->maxLevel) {
            $player->sendMessage("§cThis minion is already at maximum level!");
            return false;
        }
        $minion->setLevel($minion->getLevel() + 1);
        $player->sendMessage("§aMinion upgraded to level " . $minion->getLevel() . "!");
        return true;
    }

    private function saveMinionData(string $playerName, Position $position, string $type, int $level): void {
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

    private function loadMinions(): void {
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

    public function spawnMinionForIsland(Player $player, Position $position, string $minionType): bool {
        if (!isset($this->playerMinions[$player->getName()])) {
            $this->playerMinions[$player->getName()] = [];
        }

        $location = new \pocketmine\entity\Location(
            $position->getX(),
            $position->getY(),
            $position->getZ(),
            $position->getWorld(),
            0,
            0
        );

        $minion = $this->createMinionByType($minionType, $location);
        if ($minion === null) {
            $this->plugin->getLogger()->warning("Failed to create minion type: " . $minionType);
            return false;
        }

        $minion->spawnToAll();
        $this->playerMinions[$player->getName()][] = $minion;
        $this->saveMinionData($player->getName(), $position, $minionType, 1);
        
        $player->sendMessage("§aA " . ucfirst($minionType) . " minion has been spawned on your island!");
        return true;
    }

    public function getPlayerMinions(string $playerName): array {
        return $this->playerMinions[$playerName] ?? [];
    }

    public function removeAllPlayerMinions(string $playerName): void {
        if (isset($this->playerMinions[$playerName])) {
            foreach ($this->playerMinions[$playerName] as $minion) {
                $minion->flagForDespawn();
            }
            unset($this->playerMinions[$playerName]);
        }
    }
}