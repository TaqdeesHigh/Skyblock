<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\npc;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\OzzyNPC;

class NPCSpawnManager {

    private Main $plugin;
    private NPCDataManager $dataManager;
    /** @var array<string, OzzyNPC> */
    private array $npcs = [];
    /** @var array<string, bool> */
    private array $placingMode = [];

    public function __construct(Main $plugin, NPCDataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
        $this->loadNPCs();
    }

    public function createOzzyEgg(): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§6Ozzy's Egg");
        $egg->setLore([
            "§7Right-click to spawn Ozzy NPC",
            "§7Your island's helpful assistant"
        ]);
        return $egg;
    }

    public function spawnNPC(Player $player, Position $position): bool {
        if (isset($this->npcs[$player->getName()])) {
            $player->sendMessage("§cYou already have an Ozzy NPC! Remove the old one first.");
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

        $npc = new OzzyNPC($this->plugin, $location);
        $npc->spawnToAll();
        
        $this->npcs[$player->getName()] = $npc;
        $this->dataManager->saveNPCData($player->getName(), $position, "Ozzy");
        
        $player->sendMessage("§aOzzy has been spawned! Right-click him to interact.");
        return true;
    }

    public function removeNPC(string $playerName): bool {
        if (!isset($this->npcs[$playerName])) {
            return false;
        }

        $npc = $this->npcs[$playerName];
        $npc->flagForDespawn();
        unset($this->npcs[$playerName]);
        
        $this->dataManager->deleteNPCData($playerName);
        return true;
    }

    public function startLocationChangeMode(Player $player, OzzyNPC $npc): void {
        $this->placingMode[$player->getName()] = true;
        
        $egg = $this->createLocationEgg();
        $player->getInventory()->addItem($egg);
        
        $player->sendMessage("§aYou received a location egg!");
        $player->sendMessage("§7Right-click where you want to place " . $npc->getDisplayName() . ".");
    }

    public function createLocationEgg(): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§bLocation Egg");
        $egg->setLore([
            "§7Right-click to set new NPC location"
        ]);
        return $egg;
    }

    public function handleLocationEggUse(Player $player, Position $position): bool {
        if (!isset($this->placingMode[$player->getName()])) {
            return false;
        }

        if (!isset($this->npcs[$player->getName()])) {
            $player->sendMessage("§cYou don't have an NPC to move!");
            unset($this->placingMode[$player->getName()]);
            return false;
        }

        $npc = $this->npcs[$player->getName()];
        $npc->teleport($position);
        
        $this->dataManager->updateNPCLocation($player->getName(), $position);
        unset($this->placingMode[$player->getName()]);
        
        $player->sendMessage("§a" . $npc->getDisplayName() . " has been moved to the new location!");
        return true;
    }

    public function isInPlacingMode(string $playerName): bool {
        return isset($this->placingMode[$playerName]);
    }

    public function getNPC(string $playerName): ?OzzyNPC {
        return $this->npcs[$playerName] ?? null;
    }

    public function updateNPCName(string $playerName, string $name): void {
        if (isset($this->npcs[$playerName])) {
            $this->npcs[$playerName]->setDisplayName($name);
            $this->dataManager->updateNPCName($playerName, $name);
        }
    }

    private function loadNPCs(): void {
        $npcs = $this->dataManager->getAllNPCData();
        
        foreach ($npcs as $playerName => $npcData) {
            $worldName = $npcData["position"]["world"];
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
            
            if ($world === null) continue;
            
            $location = new \pocketmine\entity\Location(
                $npcData["position"]["x"],
                $npcData["position"]["y"],
                $npcData["position"]["z"],
                $world,
                0,
                0
            );
            
            $npc = new OzzyNPC($this->plugin, $location);
            $npc->spawnToAll();
            $npc->setDisplayName($npcData["name"]);
            
            $this->npcs[$playerName] = $npc;
        }
    }
    public function cleanupPlayer(string $playerName): void {
        unset($this->placingMode[$playerName]);
    }

}