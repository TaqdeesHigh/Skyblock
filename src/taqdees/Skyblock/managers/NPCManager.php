<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\Config;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\OzzyNPC;
use taqdees\Skyblock\managers\npc\NPCFormManager;
use taqdees\Skyblock\managers\npc\NPCSpawnManager;
use taqdees\Skyblock\managers\npc\NPCDataManager;
use taqdees\Skyblock\managers\npc\NPCIntroductionManager;

class NPCManager {

    private Main $plugin;
    private NPCFormManager $formManager;
    private NPCSpawnManager $spawnManager;
    private NPCDataManager $dataManager;
    private NPCIntroductionManager $introductionManager;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->initializeSubManagers();
    }

    private function initializeSubManagers(): void {
        $this->dataManager = new NPCDataManager($this->plugin);
        $this->spawnManager = new NPCSpawnManager($this->plugin, $this->dataManager);
        $this->formManager = new NPCFormManager($this->plugin, $this->spawnManager);
        $this->introductionManager = new NPCIntroductionManager($this->plugin);
    }

    public function createOzzyEgg(): \pocketmine\item\Item {
        return $this->spawnManager->createOzzyEgg();
    }

    public function spawnNPC(Player $player, Position $position): bool {
        return $this->spawnManager->spawnNPC($player, $position);
    }

    public function removeNPC(string $playerName): bool {
        return $this->spawnManager->removeNPC($playerName);
    }

    public function openNPCMenu(Player $player, OzzyNPC $npc): void {
        $playerName = $player->getName();
        if ($this->introductionManager->isPlayingIntroduction($playerName)) {
            return;
        }
        
        $this->introductionManager->showIntroduction($player, $npc, function() use ($player, $npc): void {
            $this->plugin->getScheduler()->scheduleDelayedTask(new \pocketmine\scheduler\ClosureTask(function() use ($player, $npc): void {
                if ($player->isOnline()) {
                    $this->formManager->openNPCMenu($player, $npc);
                }
            }), 10);
        });
    }

    public function handleLocationEggUse(Player $player, Position $position): bool {
        return $this->spawnManager->handleLocationEggUse($player, $position);
    }

    public function isInPlacingMode(string $playerName): bool {
        return $this->spawnManager->isInPlacingMode($playerName);
    }

    public function getNPC(string $playerName): ?OzzyNPC {
        return $this->spawnManager->getNPC($playerName);
    }

    public function resetIntroduction(string $playerName): void {
        $this->introductionManager->resetIntroduction($playerName);
    }

    public function getFormManager(): NPCFormManager {
        return $this->formManager;
    }

    public function getSpawnManager(): NPCSpawnManager {
        return $this->spawnManager;
    }

    public function getDataManager(): NPCDataManager {
        return $this->dataManager;
    }

    public function getIntroductionManager(): NPCIntroductionManager {
        return $this->introductionManager;
    }

    public function cleanupPlayer(string $playerName): void {
        $this->formManager->cleanupPlayer($playerName);
        $this->introductionManager->stopIntroduction($playerName);
        $this->spawnManager->cleanupPlayer($playerName);
    }

}