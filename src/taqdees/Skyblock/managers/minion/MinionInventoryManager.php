<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;

class MinionInventoryManager {

    private Main $plugin;
    private MinionUpgradeManager $upgradeManager;
    /** @var array<string, InvMenu> */
    private array $openMenus = [];

    public function __construct(Main $plugin, MinionUpgradeManager $upgradeManager) {
        $this->plugin = $plugin;
        $this->upgradeManager = $upgradeManager;
    }

    // (will be used later.)
    public function openMinionMenu(Player $player, BaseMinion $minion): void {
        $player->sendMessage("§6=== " . $minion->getDisplayName() . " ===");
        $player->sendMessage("§7Type: §e" . $minion->getMinionType());
        $player->sendMessage("§7Level: §a" . $minion->getLevel() . "/" . $minion->getMaxLevel());
        $player->sendMessage("§7Right-click to upgrade (if you have resources)");
    }

    public function openMinionInventoryMenu(Player $player, BaseMinion $minion): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName($minion->getDisplayName());
        $this->openMenus[$player->getName()] = $menu;
        
        $inventory = $menu->getInventory();
        
        $this->setupMenuLayout($inventory, $minion);
        $this->setupMenuListener($menu, $minion);
        $menu->setInventoryCloseListener(function(Player $player, \pocketmine\inventory\Inventory $inventory): void {
            unset($this->openMenus[$player->getName()]);
        });
        
        $menu->send($player);
    }

    private function setupMenuLayout($inventory, BaseMinion $minion): void {
        $grayGlassPane = $this->createGlassPane("§7", 8);
        $whiteGlassPane = $this->createGlassPane("§f§lLOCKED", 0, ["§7Upgrade minion to unlock!"]);
        for ($i = 0; $i < 54; $i++) {
            $inventory->setItem($i, $grayGlassPane);
        }
        $this->setupTopRowItems($inventory, $minion);
        $this->setupStorageSlots($inventory, $minion, $whiteGlassPane);
        $this->setupSideUtilities($inventory);
        $this->setupActionButtons($inventory, $minion);
    }

    private function createGlassPane(string $name, int $color, array $lore = []): \pocketmine\item\Item {
        $glassPane = VanillaBlocks::STAINED_GLASS_PANE()->asItem();
        $glassPane->setCustomName($name);
        if (!empty($lore)) {
            $glassPane->setLore($lore);
        }
        $glassPane->getNamedTag()->setByte("color", $color);
        return $glassPane;
    }

    private function countStorageItems(BaseMinion $minion): int {
        return $minion->getInventoryItemCount();
    }

    private function setupTopRowItems($inventory, BaseMinion $minion): void {
        if ($minion->getLevel() < $minion->getMaxLevel()) {
            $upgradeButton = $this->createUpgradeButton($minion);
            $inventory->setItem(3, $upgradeButton);
        } else {
            $maxLevelItem = $this->createMaxLevelItem();
            $inventory->setItem(3, $maxLevelItem);
        }
        $minionInfo = $this->createMinionInfoItem($minion);
        $inventory->setItem(4, $minionInfo);
        $skinButton = $this->createSkinButton();
        $inventory->setItem(5, $skinButton);
    }

    private function createUpgradeButton(BaseMinion $minion): \pocketmine\item\Item {
        $upgradeCost = $this->upgradeManager->getUpgradeCost($minion);
        $benefits = $this->upgradeManager->getUpgradeBenefits($minion);
        
        $upgradeButton = VanillaItems::EXPERIENCE_BOTTLE();
        $upgradeButton->setCustomName("§bUpgrade Minion");
        $upgradeButton->setLore([
            "§7Current Level: §a" . $minion->getLevel(),
            "§7Next Level: §a" . ($minion->getLevel() + 1),
            "",
            "§7Upgrade Cost:",
            "§8• §7" . $upgradeCost["description"],
            "",
            "§7Benefits:",
            "§8• §7" . $benefits["description"][0],
            "§8• §7" . $benefits["description"][1],
            "",
            "§eClick to upgrade!"
        ]);
        return $upgradeButton;
    }

    private function createMaxLevelItem(): \pocketmine\item\Item {
        $maxLevelItem = VanillaBlocks::BEACON()->asItem();
        $maxLevelItem->setCustomName("§6§lMAX LEVEL");
        $maxLevelItem->setLore([
            "§7This minion is at maximum level!",
            "§7All upgrades have been unlocked."
        ]);
        return $maxLevelItem;
    }

    private function createMinionInfoItem(BaseMinion $minion): \pocketmine\item\Item {
        $minionInfo = VanillaBlocks::MOB_HEAD()->asItem();
        $minionInfo->setCustomName("§6" . $minion->getDisplayName());
        $minionInfo->setLore([
            "§7Type: §e" . ucfirst($minion->getMinionType()),
            "§7Level: §a" . $minion->getLevel() . "§7/§a" . $minion->getMaxLevel(),
            "§7Status: §2Active",
            "",
            "§7This minion automatically",
            "§7works for you while you're away!"
        ]);
        return $minionInfo;
    }

    private function createSkinButton(): \pocketmine\item\Item {
        $skinButton = VanillaBlocks::MOB_HEAD()->asItem();
        $skinButton->setCustomName("§dMinion Skin");
        $skinButton->setLore([
            "§7Change your minion's appearance!",
            "",
            "§7Current skin: §eDefault",
            "§8(Click to browse skins)"
        ]);
        return $skinButton;
    }

    private function setupStorageSlots($inventory, BaseMinion $minion, $whiteGlassPane): void {
        $storageSlots = [
            21, 22, 23, 24, 25,
            30, 31, 32, 33, 34,
            39, 40, 41, 42, 43
        ];
        
        $unlockedSlots = min($minion->getMaxInventorySlots(), count($storageSlots));
        $minionInventory = $minion->getMinionInventory();
        
        for ($i = 0; $i < count($storageSlots); $i++) {
            $slot = $storageSlots[$i];
            if ($i < $unlockedSlots) {
                if (isset($minionInventory[$i])) {
                    $inventory->setItem($slot, $minionInventory[$i]);
                } else {
                    $inventory->clear($slot);
                }
            } else {
                $inventory->setItem($slot, $whiteGlassPane);
            }
        }
    }

    private function setupSideUtilities($inventory): void {
        $fuelSlot = VanillaBlocks::FURNACE()->asItem();
        $fuelSlot->setCustomName("§6Fuel Slot");
        $fuelSlot->setLore([
            "§7Place fuel here to increase",
            "§7minion speed and efficiency!",
            "",
            "§7Accepted fuels:",
            "§8• §7Coal (+10% speed)",
            "§8• §7Enchanted Coal (+25% speed)",
            "§8• §7Block of Coal (+50% speed)"
        ]);
        $inventory->setItem(19, $fuelSlot);
        $autoCollectSlot = VanillaBlocks::HOPPER()->asItem();
        $autoCollectSlot->setCustomName("§9Auto Collect");
        $autoCollectSlot->setLore([
            "§7Place an auto-collect item here to",
            "§7automatically collect minion drops!",
            "",
            "§7Available items:",
            "§8• §7Budget Hopper (slow collection)",
            "§8• §7Enchanted Hopper (fast collection)",
            "§8• §7Super Compactor (compacts items)"
        ]);
        $inventory->setItem(37, $autoCollectSlot);
    }

    private function setupActionButtons($inventory, BaseMinion $minion): void {
        $collectButton = VanillaBlocks::CHEST()->asItem();
        $collectButton->setCustomName("§aCollect All");
        $collectButton->setLore([
            "§7Click to collect all items",
            "§7from this minion's storage!",
            "",
            "§7Items in storage: §e" . $this->countStorageItems($minion)
        ]);
        $inventory->setItem(48, $collectButton);
        $pickupButton = VanillaBlocks::BEDROCK()->asItem();
        $pickupButton->setCustomName("§cPickup Minion");
        $pickupButton->setLore([
            "§7Break this minion and add it",
            "§7back to your inventory.",
            "",
            "§c§lWARNING:",
            "§7This will remove the minion",
            "§7from this location permanently!"
        ]);
        $inventory->setItem(50, $pickupButton);
    }

    private function setupMenuListener(InvMenu $menu, BaseMinion $minion): void {
        $storageSlots = [21, 22, 23, 24, 25, 30, 31, 32, 33, 34, 39, 40, 41, 42, 43];
        $unlockedSlots = min($minion->getLevel() * 2, count($storageSlots));
        
        $menu->setListener(function(InvMenuTransaction $transaction) use ($minion, $storageSlots, $unlockedSlots, $menu): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $slot = $transaction->getAction()->getSlot();
            $clickedItem = $transaction->getItemClicked();
            $storageSlotIndex = array_search($slot, $storageSlots);
            
            if ($storageSlotIndex !== false && $storageSlotIndex < $unlockedSlots) {
                $this->plugin->getScheduler()->scheduleDelayedTask(
                    new \pocketmine\scheduler\ClosureTask(function() use ($menu, $minion, $storageSlots, $unlockedSlots): void {
                        $this->syncMinionInventoryFromMenu($menu, $minion, $storageSlots, $unlockedSlots);
                    }), 1
                );
                return $transaction->continue();
            }
            
            $this->handleButtonClick($player, $minion, $slot, $clickedItem);
            
            return $transaction->discard();
        });
    }

    private function syncMinionInventoryFromMenu(InvMenu $menu, BaseMinion $minion, array $storageSlots, int $unlockedSlots): void {
        $menuInventory = $menu->getInventory();
        $newMinionInventory = [];
        for ($i = 0; $i < $unlockedSlots; $i++) {
            $slot = $storageSlots[$i];
            $item = $menuInventory->getItem($slot);
            
            if (!$item->isNull() && $item->getCount() > 0) {
                $newMinionInventory[] = clone $item;
            }
        }
        $minion->syncInventoryFromArray($newMinionInventory);
    }

    private function handleButtonClick(Player $player, BaseMinion $minion, int $slot, \pocketmine\item\Item $clickedItem): void {
        switch ($slot) {
            case 3:
                if ($clickedItem->getCustomName() === "§bUpgrade Minion") {
                    $this->upgradeManager->upgradeMinion($player, $minion);
                    $this->refreshMenu($player, $minion);
                }
                break;
                
            case 5: 
                if ($clickedItem->getCustomName() === "§dMinion Skin") {
                    $player->sendMessage("§dMinion skin feature coming soon!");
                }
                break;
                
            case 48:
                if ($clickedItem->getCustomName() === "§aCollect All") {
                    $this->collectMinionItems($player, $minion);
                }
                break;
                
            case 50:
                if ($clickedItem->getCustomName() === "§cPickup Minion") {
                    $this->pickupMinion($player, $minion);
                    $player->removeCurrentWindow();
                }
                break;
                
            case 19:
            case 37:
                $player->sendMessage("§7This feature is coming soon!");
                break;
        }
    }

    private function refreshMenu(Player $player, BaseMinion $minion): void {
        $player->removeCurrentWindow();
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new \pocketmine\scheduler\ClosureTask(function() use ($player, $minion): void {
                if ($player->isOnline()) {
                    $this->openMinionInventoryMenu($player, $minion);
                }
            }), 5
        );
    }

    private function collectMinionItems(Player $player, BaseMinion $minion): void {
        $collected = $minion->collectItemsFromInventory($player);
        if ($collected > 0) {
            $player->sendMessage("§aCollected " . $collected . " item stacks from minion!");
            $this->updateStorageDisplay($player, $minion);
            $this->updateCollectButton($player, $minion);
        } else {
            $player->sendMessage("§cNo items to collect or your inventory is full!");
        }
    }

    private function updateStorageDisplay(Player $player, BaseMinion $minion): void {
        if (!isset($this->openMenus[$player->getName()])) {
            return;
        }

        $menu = $this->openMenus[$player->getName()];
        $inventory = $menu->getInventory();
        
        $storageSlots = [21, 22, 23, 24, 25, 30, 31, 32, 33, 34, 39, 40, 41, 42, 43];
        $unlockedSlots = min($minion->getMaxInventorySlots(), count($storageSlots));
        $minionInventory = $minion->getMinionInventory();
        for ($i = 0; $i < $unlockedSlots; $i++) {
            $slot = $storageSlots[$i];
            $inventory->clear($slot);
        }
        
        for ($i = 0; $i < count($minionInventory) && $i < $unlockedSlots; $i++) {
            $slot = $storageSlots[$i];
            if (!$minionInventory[$i]->isNull()) {
                $inventory->setItem($slot, $minionInventory[$i]);
            }
        }
    }
    private function updateCollectButton(Player $player, BaseMinion $minion): void {
        if (!isset($this->openMenus[$player->getName()])) {
            return;
        }

        $menu = $this->openMenus[$player->getName()];
        $inventory = $menu->getInventory();
        
        $collectButton = VanillaBlocks::CHEST()->asItem();
        $collectButton->setCustomName("§aCollect All");
        $collectButton->setLore([
            "§7Click to collect all items",
            "§7from this minion's storage!",
            "",
            "§7Items in storage: §e" . $this->countStorageItems($minion)
        ]);
        
        $inventory->setItem(48, $collectButton);
    }

    private function pickupMinion(Player $player, BaseMinion $minion): void {
        $spawnManager = new MinionSpawnManager($this->plugin, $this->plugin->getMinionManager()->getDataManager());
        $minionEgg = $spawnManager->createMinionEgg($minion->getMinionType(), $minion->getLevel());
        
        $this->collectMinionItems($player, $minion);
        if ($player->getInventory()->canAddItem($minionEgg)) {
            $player->getInventory()->addItem($minionEgg);
            $player->sendMessage("§aMinion picked up successfully!");
        } else {
            $player->getWorld()->dropItem($player->getPosition(), $minionEgg);
            $player->sendMessage("§eMinion egg dropped on the ground (inventory full)!");
        }
        $dataManager = $this->plugin->getMinionManager()->getDataManager();
        if (method_exists($dataManager, 'removeMinionFromData')) {
            $dataManager->removeMinionFromData($player->getName(), $minion);
        }
        $minion->flagForDespawn();
    }
}