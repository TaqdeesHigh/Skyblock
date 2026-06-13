<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;

class MinionUpgradeMenuManager {

    private Main $plugin;
    private MinionUpgradeManager $upgradeManager;
    private MinionInventoryManager $inventoryManager;

    public function __construct(
        Main $plugin,
        MinionUpgradeManager $upgradeManager,
        MinionInventoryManager $inventoryManager
    ) {
        $this->plugin           = $plugin;
        $this->upgradeManager   = $upgradeManager;
        $this->inventoryManager = $inventoryManager;
    }

    public function openUpgradeMenu(Player $player, BaseMinion $minion): void {
        $currentLevel = $minion->getLevel();
        $targetLevel  = $currentLevel + 1;

        if ($targetLevel > $minion->getMaxLevel()) {
            $player->sendMessage("§cThis minion is already at maximum level!");
            return;
        }

        if ($this->upgradeManager->canAffordUpgrade($player, $minion, $targetLevel)) {
            $success = $this->upgradeManager->upgradeMinion($player, $minion);
            if ($success && $player->isOnline()) {
                $this->inventoryManager->openMinionInventoryMenu($player, $minion);
            }
            return;
        }

        $this->openUpgradeCraftingUI($player, $minion, $targetLevel);
    }

    private function openUpgradeCraftingUI(Player $player, BaseMinion $minion, int $targetLevel): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $currentName = $this->upgradeManager->getLevelName($minion->getLevel());
        $targetName  = $this->upgradeManager->getLevelName($targetLevel);
        $menu->setName("§6Upgrade: §e" . ucfirst($minion->getMinionType()) . " §7(" . $currentName . " → " . $targetName . ")");

        $inv = $menu->getInventory();
        $craftingSlots = [11, 12, 13, 20, 21, 22, 29, 30, 31];

        $this->fillCraftingLayout($inv, $minion, $targetLevel);

        $menu->setInventoryCloseListener(function (Player $player, \pocketmine\inventory\Inventory $inventory) use ($craftingSlots): void {
            foreach ($craftingSlots as $slot) {
                $item = $inventory->getItem($slot);
                if (!$item->isNull() && !$this->isGhostItem($item)) {
                    if ($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                }
            }
        });

        $menu->setListener(function (InvMenuTransaction $transaction) use ($minion, $targetLevel, $craftingSlots, $menu): InvMenuTransactionResult {
            $player      = $transaction->getPlayer();
            $slot        = $transaction->getAction()->getSlot();
            $clickedItem = $transaction->getItemClicked();

            if ($slot === 49) {
                $player->removeCurrentWindow();
                $this->plugin->getScheduler()->scheduleDelayedTask(
                    new \pocketmine\scheduler\ClosureTask(function () use ($player, $minion): void {
                        if ($player->isOnline()) {
                            $this->inventoryManager->openMinionInventoryMenu($player, $minion);
                        }
                    }), 2
                );
                return $transaction->discard();
            }

            if (!in_array($slot, $craftingSlots)) {
                return $transaction->discard();
            }

            if ($this->isGhostItem($clickedItem)) {
                return $transaction->discard();
            }

            $this->plugin->getScheduler()->scheduleDelayedTask(
                new \pocketmine\scheduler\ClosureTask(function () use ($player, $minion, $targetLevel, $craftingSlots, $menu): void {
                    if (!$player->isOnline()) return;
                    $inv = $menu->getInventory();
                    if ($this->gridMatchesRecipe($inv, $craftingSlots, $minion, $targetLevel)) {
                        $this->consumeGridItems($inv, $craftingSlots);
                        $player->removeCurrentWindow();
                        $this->plugin->getScheduler()->scheduleDelayedTask(
                            new \pocketmine\scheduler\ClosureTask(function () use ($player, $minion, $targetLevel): void {
                                if (!$player->isOnline()) return;
                                $minion->setLevel($targetLevel);
                                $levelName = $this->upgradeManager->getLevelName($targetLevel);
                                $player->sendMessage("§aMinion upgraded! §e" . ucfirst($minion->getMinionType()) . " Minion §ais now §6" . $levelName . "§a!");
                                $player->sendMessage("§7Speed and storage have been improved.");
                                $this->inventoryManager->openMinionInventoryMenu($player, $minion);
                            }), 2
                        );
                    } else {
                        $this->updateResultSlot($inv, $craftingSlots, $minion, $targetLevel);
                    }
                }), 1
            );

            return $transaction->continue();
        });

        $menu->send($player);
    }

    private function gridMatchesRecipe(\pocketmine\inventory\Inventory $inv, array $craftingSlots, BaseMinion $minion, int $targetLevel): bool {
        $recipe = $this->upgradeManager->getRingRecipe($minion, $targetLevel);
        $flatRecipe = [
            $recipe[0][0], $recipe[0][1], $recipe[0][2],
            $recipe[1][0], $recipe[1][1], $recipe[1][2],
            $recipe[2][0], $recipe[2][1], $recipe[2][2],
        ];

        foreach ($craftingSlots as $i => $slot) {
            $gridItem   = $inv->getItem($slot);
            $recipeItem = $flatRecipe[$i];

            if ($gridItem->isNull() || $this->isGhostItem($gridItem)) {
                return false;
            }
            if ($gridItem->getCount() < $recipeItem->getCount()) {
                return false;
            }
            if ($gridItem->getTypeId() !== $recipeItem->getTypeId()) {
                return false;
            }
        }

        return true;
    }

    private function consumeGridItems(\pocketmine\inventory\Inventory $inv, array $craftingSlots): void {
        foreach ($craftingSlots as $slot) {
            $inv->setItem($slot, VanillaItems::AIR());
        }
    }

    private function updateResultSlot(\pocketmine\inventory\Inventory $inv, array $craftingSlots, BaseMinion $minion, int $targetLevel): void {
        $recipe = $this->upgradeManager->getRingRecipe($minion, $targetLevel);
        $flatRecipe = [
            $recipe[0][0], $recipe[0][1], $recipe[0][2],
            $recipe[1][0], $recipe[1][1], $recipe[1][2],
            $recipe[2][0], $recipe[2][1], $recipe[2][2],
        ];

        $allFilled = true;
        foreach ($craftingSlots as $i => $slot) {
            $gridItem   = $inv->getItem($slot);
            $recipeItem = $flatRecipe[$i];
            if ($gridItem->isNull() || $gridItem->getTypeId() !== $recipeItem->getTypeId() || $gridItem->getCount() < $recipeItem->getCount()) {
                $allFilled = false;
                break;
            }
        }

        if ($allFilled) {
            $resultItem = VanillaBlocks::MOB_HEAD()->asItem();
            $resultItem->setCustomName("§aUpgraded " . ucfirst($minion->getMinionType()) . " Minion");
            $resultItem->setLore(["§7Crafting grid complete!", "§7Will upgrade automatically."]);
            $inv->setItem(25, $resultItem);
        } else {
            $inv->setItem(25, VanillaItems::AIR());
        }
    }

    private function fillCraftingLayout(\pocketmine\inventory\Inventory $inv, BaseMinion $minion, int $targetLevel): void {
        $gray = $this->makePane("§r", 7);

        for ($i = 0; $i < 54; $i++) {
            $inv->setItem($i, clone $gray);
        }

        $craftingAreaSlots = [10, 11, 12, 13, 14, 19, 20, 21, 22, 23, 28, 29, 30, 31, 32];
        $lightGray = $this->makePane("§r", 8);
        foreach ($craftingAreaSlots as $slot) {
            $inv->setItem($slot, clone $lightGray);
        }

        $craftingSlots = [11, 12, 13, 20, 21, 22, 29, 30, 31];
        foreach ($craftingSlots as $slot) {
            $inv->setItem($slot, VanillaItems::AIR());
        }

        $recipe = $this->upgradeManager->getRingRecipe($minion, $targetLevel);
        $flatRecipe = [
            $recipe[0][0], $recipe[0][1], $recipe[0][2],
            $recipe[1][0], $recipe[1][1], $recipe[1][2],
            $recipe[2][0], $recipe[2][1], $recipe[2][2],
        ];

        foreach ($craftingSlots as $i => $slot) {
            $ghost = clone $flatRecipe[$i];
            $ghost->setLore(["§7Place §e" . $ghost->getCount() . "x " . $ghost->getName() . " §7here."]);
            $ghost->getNamedTag()->setByte("upgrade_ghost", 1);
            $inv->setItem($slot, $ghost);
        }

        $inv->setItem(25, VanillaItems::AIR());

        $arrow = VanillaItems::ARROW();
        $arrow->setCustomName("§aResult");
        $arrow->setLore(["§7Fill the grid with the correct items", "§7to upgrade your minion!"]);
        $inv->setItem(24, $arrow);

        $infoItem = VanillaBlocks::MOB_HEAD()->asItem();
        $currentName = $this->upgradeManager->getLevelName($minion->getLevel());
        $targetName  = $this->upgradeManager->getLevelName($targetLevel);
        $countPerSlot = $this->upgradeManager->getMaterialCountPerSlot($targetLevel);
        $material = $this->upgradeManager->getPrimaryMaterial($minion);
        $tool = $this->upgradeManager->getCenterTool($minion, $targetLevel);
        $infoItem->setCustomName("§6" . ucfirst($minion->getMinionType()) . " Minion Upgrade");
        $infoItem->setLore([
            "§7Current: §e" . $currentName . " §7(Lv. " . $minion->getLevel() . ")",
            "§7Target:  §a" . $targetName  . " §7(Lv. " . $targetLevel . ")",
            "",
            "§7Required:",
            "§8• §7" . ($countPerSlot * 8) . "x §e" . $material->getName(),
            "§8• §71x §e" . $tool->getName(),
            "",
            "§7Fill the crafting grid to upgrade!",
        ]);
        $inv->setItem(4, $infoItem);

        $cancel = VanillaBlocks::STAINED_GLASS_PANE()->asItem();
        $cancel->setCustomName("§c§lCANCEL");
        $cancel->setLore(["§7Go back to the minion menu."]);
        $cancel->getNamedTag()->setByte("color", 14);
        $inv->setItem(49, $cancel);
    }

    private function isGhostItem(\pocketmine\item\Item $item): bool {
        return $item->getNamedTag()->getByte("upgrade_ghost", 0) === 1;
    }

    private function makePane(string $name, int $color): \pocketmine\item\Item {
        $pane = VanillaBlocks::STAINED_GLASS_PANE()->asItem();
        $pane->setCustomName($name);
        $pane->getNamedTag()->setByte("color", $color);
        return $pane;
    }
}