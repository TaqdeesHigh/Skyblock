<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;

class MinionUpgradeManager {

    private Main $plugin;
    private MinionDataManager $dataManager;

    public function __construct(Main $plugin, MinionDataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function upgradeMinion(Player $player, BaseMinion $minion): bool {
        if ($minion->getLevel() >= $minion->getMaxLevel()) {
            $player->sendMessage("§cThis minion is already at maximum level!");
            return false;
        }
        if (!$this->hasUpgradeResources($player, $minion)) {
            $player->sendMessage("§cYou don't have enough resources to upgrade this minion!");
            return false;
        }
        $this->consumeUpgradeResources($player, $minion);
        $player->sendMessage("§aMinion upgraded to level " . $minion->getLevel() . "!");
        $this->updateMinionLevelInData($player->getName(), $minion);
        
        return true;
    }

    private function hasUpgradeResources(Player $player, BaseMinion $minion): bool {
        // TODO: Implement resource checking logic
        return true;
    }

    private function consumeUpgradeResources(Player $player, BaseMinion $minion): void {
        // TODO: Implement resource consumption logic
    }

    private function updateMinionLevelInData(string $playerName, BaseMinion $minion): void {
        // TODO: Update the minion level in the config file
    }

    public function getUpgradeCost(BaseMinion $minion): array {
        $level = $minion->getLevel();
        $minionType = $minion->getMinionType();
        
        return [
            "type" => $minionType,
            "amount" => $level * 10,
            "description" => ($level * 10) . " " . ucfirst($minionType)
        ];
    }

    public function getUpgradeBenefits(BaseMinion $minion): array {
        return [
            "speed_increase" => "10%",
            "storage_slots" => 2,
            "description" => [
                "+10% Speed",
                "+2 Storage Slots"
            ]
        ];
    }
}