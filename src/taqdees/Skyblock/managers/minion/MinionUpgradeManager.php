<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;

class MinionUpgradeManager {

    private Main $plugin;
    private MinionDataManager $dataManager;

    public const LEVEL_NAMES = [
        1 => "Starter",
        2 => "Apprentice",
        3 => "Journeyman",
        4 => "Expert",
        5 => "Master",
    ];

    private const MATERIAL_COUNT_PER_SLOT = [
        2 => 16,
        3 => 32,
        4 => 64,
        5 => 128,
    ];

    public function __construct(Main $plugin, MinionDataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function getPrimaryMaterial(BaseMinion $minion): Item {
        $profName = strtolower($minion->getProfession()?->getName() ?? '');
        return match($profName) {
            'farming'    => $this->getFarmingMaterial($minion),
            'woodcutting' => VanillaBlocks::OAK_LOG()->asItem(),
            default      => VanillaBlocks::COBBLESTONE()->asItem(),
        };
    }

    private function getFarmingMaterial(BaseMinion $minion): Item {
        return match($minion->getMinionType()) {
            'carrot'   => VanillaItems::CARROT(),
            'potato'   => VanillaItems::POTATO(),
            'melon'    => VanillaItems::MELON(),
            'pumpkin'  => VanillaBlocks::PUMPKIN()->asItem(),
            default    => VanillaItems::WHEAT(),
        };
    }

    public function getCenterTool(BaseMinion $minion, int $targetLevel): Item {
        $profession = $minion->getProfession();
        if ($profession === null) return VanillaItems::STICK();
        $tools = $profession->getTools();
        $index = min($targetLevel - 1, count($tools) - 1);
        return $tools[$index] ?? VanillaItems::STICK();
    }

    public function getMaterialCountPerSlot(int $targetLevel): int {
        return self::MATERIAL_COUNT_PER_SLOT[$targetLevel] ?? 16;
    }

    public function upgradeMinion(Player $player, BaseMinion $minion): bool {
        $currentLevel = $minion->getLevel();

        if ($currentLevel >= $minion->getMaxLevel()) {
            $player->sendMessage("§cThis minion is already at maximum level (Master)!");
            return false;
        }

        $targetLevel = $currentLevel + 1;

        if (!$this->canAffordUpgrade($player, $minion, $targetLevel)) {
            return false;
        }

        $material = $this->getPrimaryMaterial($minion);
        $countPerSlot = $this->getMaterialCountPerSlot($targetLevel);
        $totalMaterial = clone $material;
        $totalMaterial->setCount($countPerSlot * 8);
        $player->getInventory()->removeItem($totalMaterial);

        $tool = $this->getCenterTool($minion, $targetLevel);
        $toolToRemove = clone $tool;
        $toolToRemove->setCount(1);
        $player->getInventory()->removeItem($toolToRemove);

        $minion->setLevel($targetLevel);
        $this->updateMinionLevelInData($player->getName(), $minion);

        $levelName = self::LEVEL_NAMES[$targetLevel] ?? "Level $targetLevel";
        $player->sendMessage("§aMinion upgraded! §e" . ucfirst($minion->getMinionType()) . " Minion §ais now §6" . $levelName . "§a!");
        $player->sendMessage("§7Speed and storage have been improved.");

        return true;
    }

    public function canAffordUpgrade(Player $player, BaseMinion $minion, int $targetLevel): bool {
        $material = $this->getPrimaryMaterial($minion);
        $countPerSlot = $this->getMaterialCountPerSlot($targetLevel);
        $totalNeeded = $countPerSlot * 8;

        $haveMaterial = $this->countPlayerItem($player, $material);
        if ($haveMaterial < $totalNeeded) return false;

        $tool = $this->getCenterTool($minion, $targetLevel);
        $haveTool = $this->countPlayerItem($player, $tool);
        if ($haveTool < 1) return false;

        return true;
    }

    public function getUpgradeCostDetails(Player $player, BaseMinion $minion, int $targetLevel): array {
        $material = $this->getPrimaryMaterial($minion);
        $countPerSlot = $this->getMaterialCountPerSlot($targetLevel);
        $totalNeeded = $countPerSlot * 8;
        $tool = $this->getCenterTool($minion, $targetLevel);

        $haveMaterial = $this->countPlayerItem($player, $material);
        $haveTool = $this->countPlayerItem($player, $tool);

        return [
            [
                'item'  => $material,
                'count' => $totalNeeded,
                'name'  => $material->getName(),
                'have'  => $haveMaterial,
                'met'   => $haveMaterial >= $totalNeeded,
            ],
            [
                'item'  => $tool,
                'count' => 1,
                'name'  => $tool->getName(),
                'have'  => $haveTool,
                'met'   => $haveTool >= 1,
            ],
        ];
    }

    public function getUpgradeCost(BaseMinion $minion): array {
        $targetLevel = $minion->getLevel() + 1;
        $material = $this->getPrimaryMaterial($minion);
        $countPerSlot = $this->getMaterialCountPerSlot($targetLevel);
        $tool = $this->getCenterTool($minion, $targetLevel);

        return [
            'description' => ($countPerSlot * 8) . "x " . $material->getName() . ", 1x " . $tool->getName(),
        ];
    }

    public function getUpgradeBenefits(BaseMinion $minion): array {
        $nextLevel = $minion->getLevel() + 1;
        $speedBonus = ($nextLevel - 1) * 10;

        return [
            'speed_increase' => $speedBonus . "%",
            'storage_slots'  => 2,
            'description'    => [
                "Faster work speed (§aTick cooldown §7reduced)",
                "+§a2 §7storage slots",
                "Tool upgraded to §e" . $this->getToolNameForLevel($minion, $nextLevel),
            ],
        ];
    }

    public function getLevelName(int $level): string {
        return self::LEVEL_NAMES[$level] ?? "Level $level";
    }

    public function getRingRecipe(BaseMinion $minion, int $targetLevel): array {
        $material = clone $this->getPrimaryMaterial($minion);
        $material->setCount($this->getMaterialCountPerSlot($targetLevel));
        $tool = clone $this->getCenterTool($minion, $targetLevel);
        $tool->setCount(1);

        return [
            [$material, $material, $material],
            [$material, $tool,     $material],
            [$material, $material, $material],
        ];
    }

    private function countPlayerItem(Player $player, Item $item): int {
        $total = 0;
        foreach ($player->getInventory()->getContents() as $invItem) {
            if ($invItem->getTypeId() === $item->getTypeId()) {
                $total += $invItem->getCount();
            }
        }
        return $total;
    }

    private function getToolNameForLevel(BaseMinion $minion, int $level): string {
        $profession = $minion->getProfession();
        if ($profession === null) return "Unknown";
        $tools = $profession->getTools();
        $index = min($level - 1, count($tools) - 1);
        return $tools[$index]?->getName() ?? "Unknown";
    }

    private function updateMinionLevelInData(string $playerName, BaseMinion $minion): void {
        $minions = $this->plugin->getDataFolder() . "minions.yml";
        try {
            $config = new \pocketmine\utils\Config($minions, \pocketmine\utils\Config::YAML);
            $all = $config->get("minions", []);

            if (!isset($all[$playerName])) return;

            $minionPos = $minion->getPosition();
            foreach ($all[$playerName] as &$minionData) {
                $pos = $minionData['position'];
                if (
                    abs($pos['x'] - $minionPos->x) < 0.5 &&
                    abs($pos['y'] - $minionPos->y) < 0.5 &&
                    abs($pos['z'] - $minionPos->z) < 0.5
                ) {
                    $minionData['level'] = $minion->getLevel();
                    break;
                }
            }
            unset($minionData);

            $config->set("minions", $all);
            $config->save();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->warning("Failed to update minion level in config: " . $e->getMessage());
        }
    }
}