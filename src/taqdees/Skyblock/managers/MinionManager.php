<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\world\Position;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\managers\minion\MinionDataManager;
use taqdees\Skyblock\managers\minion\MinionInventoryManager;
use taqdees\Skyblock\managers\minion\MinionSpawnManager;
use taqdees\Skyblock\managers\minion\MinionUpgradeManager;

class MinionManager {

    private Main $plugin;
    private MinionDataManager $dataManager;
    private MinionInventoryManager $inventoryManager;
    private MinionSpawnManager $spawnManager;
    private MinionUpgradeManager $upgradeManager;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->dataManager = new MinionDataManager($plugin);
        $this->spawnManager = new MinionSpawnManager($plugin, $this->dataManager);
        $this->upgradeManager = new MinionUpgradeManager($plugin, $this->dataManager);
        $this->inventoryManager = new MinionInventoryManager($plugin, $this->upgradeManager);
        
        $this->loadMinions();
    }

    public function createMinionEgg(string $minionType): \pocketmine\item\Item {
        return $this->spawnManager->createMinionEgg($minionType);
    }

    public function spawnMinionFromEgg(Player $player, Position $position, \pocketmine\item\Item $egg): bool {
        return $this->spawnManager->spawnMinionFromEgg($player, $position, $egg);
    }

    public function createMinionEggWithLevel(string $minionType, int $level): \pocketmine\item\Item {
        return $this->spawnManager->createMinionEgg($minionType, $level);
    }

    public function getDataManager(): MinionDataManager {
        return $this->dataManager;
    }

    public function openMinionMenu(Player $player, BaseMinion $minion): void {
        $this->inventoryManager->openMinionMenu($player, $minion);
    }

    public function openMinionInventoryMenu(Player $player, BaseMinion $minion): void {
        $this->inventoryManager->openMinionInventoryMenu($player, $minion);
    }

    public function upgradeMinion(Player $player, BaseMinion $minion): bool {
        return $this->upgradeManager->upgradeMinion($player, $minion);
    }

    public function spawnMinionForIsland(Player $player, Position $position, string $minionType): bool {
        return $this->spawnManager->spawnMinionForIsland($player, $position, $minionType);
    }

    public function getPlayerMinions(string $playerName): array {
        return $this->dataManager->getPlayerMinions($playerName);
    }

    public function removeAllPlayerMinions(string $playerName): void {
        $this->dataManager->removeAllPlayerMinions($playerName);
    }

    private function loadMinions(): void {
        $this->dataManager->loadMinions();
    }
}