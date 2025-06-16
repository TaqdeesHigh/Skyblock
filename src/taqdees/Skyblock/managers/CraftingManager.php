<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\traits\PluginOwned;
use taqdees\Skyblock\crafting\RecipeRegistry;
use taqdees\Skyblock\crafting\MultiPatternRecipe;

class CraftingManager {
    use PluginOwned;

    private RecipeRegistry $recipeRegistry;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->recipeRegistry = RecipeRegistry::getInstance();
    }

    private function initializeRecipes(): void {
        $this->addRecipe([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [null, null, null],
            [null, null, null]
        ], VanillaItems::STICK()->setCount(4));
        $this->addRecipe([
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [null, null, null]
        ], VanillaItems::STICK()->setCount(4));
    }

    private function addRecipe(array $pattern, Item $result): void {
        $this->recipes[] = [
            'pattern' => $pattern,
            'result' => $result
        ];
    }

    public function openCraftingUI(Player $player): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName("§8Craft Item");
        
        $inventory = $menu->getInventory();
        $craftingSlots = [11, 12, 13, 20, 21, 22, 29, 30, 31];
        $resultSlot = 25; 
        $this->setupHypixelUI($inventory);
        $menu->setInventoryCloseListener(function(Player $player, \pocketmine\inventory\Inventory $inventory) use ($craftingSlots): void {
            $this->returnCraftingItems($player, $inventory, $craftingSlots);
        });
        
        $menu->setListener(function(InvMenuTransaction $transaction) use ($craftingSlots, $resultSlot): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $slot = $transaction->getAction()->getSlot();
            if (!in_array($slot, array_merge($craftingSlots, [$resultSlot]))) {
                return $transaction->discard();
            }
            if ($slot === $resultSlot) {
                return $this->handleResultSlotClick($transaction, $craftingSlots, $resultSlot);
            }
            $this->plugin->getScheduler()->scheduleDelayedTask(
                new \pocketmine\scheduler\ClosureTask(function() use ($player, $transaction): void {
                    $this->updateCraftingResult($player, $transaction->getAction()->getInventory());
                }), 1
            );
            
            return $transaction->continue();
        });
        
        $menu->send($player);
    }

    private function returnCraftingItems(Player $player, \pocketmine\inventory\Inventory $inventory, array $craftingSlots): void {
        foreach ($craftingSlots as $slot) {
            $item = $inventory->getItem($slot);
            if (!$item->isNull()) {
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else {
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                    $player->sendMessage("§eYour inventory was full, so items were dropped!");
                }
            }
        }
    }

    private function setupHypixelUI(\pocketmine\inventory\Inventory $inventory): void {
        $borderGlass = VanillaBlocks::STAINED_GLASS_PANE()->asItem();
        $borderGlass->setCustomName("§r");
        $borderGlass->getNamedTag()->setByte("color", 7); 
        
        $craftingGlass = VanillaBlocks::STAINED_GLASS_PANE()->asItem();
        $craftingGlass->setCustomName("§r");
        $craftingGlass->getNamedTag()->setByte("color", 8);
        
        for ($i = 0; $i < 54; $i++) {
            $inventory->setItem($i, clone $borderGlass);
        }
        
        $craftingAreaSlots = [10, 11, 12, 13, 14, 19, 20, 21, 22, 23, 28, 29, 30, 31, 32];
        foreach ($craftingAreaSlots as $slot) {
            $inventory->setItem($slot, clone $craftingGlass);
        }
        
        $craftingSlots = [11, 12, 13, 20, 21, 22, 29, 30, 31];
        foreach ($craftingSlots as $slot) {
            $inventory->setItem($slot, VanillaItems::AIR());
        }
        $inventory->setItem(25, VanillaItems::AIR());
        $craftingTable = VanillaBlocks::CRAFTING_TABLE()->asItem();
        $craftingTable->setCustomName("§aCrafting Table");
        $craftingTable->setLore([
            "§7Craft items using the grid to the right.",
            "§7Place items in the crafting grid to see",
            "§7what you can craft!"
        ]);
        $inventory->setItem(4, $craftingTable);
        $arrow = VanillaItems::ARROW();
        $arrow->setCustomName("§aResult");
        $arrow->setLore([
            "§7This is the result of your crafting recipe.",
            "§7Click to craft!"
        ]);
        $inventory->setItem(24, $arrow);
        
        $quickCraft = VanillaItems::ENCHANTED_BOOK();
        $quickCraft->setCustomName("§aQuick Crafting");
        $quickCraft->setLore([
            "§7Instantly craft items without having",
            "§7to click multiple times!",
            "",
            "§cComing Soon!"
        ]);
        $inventory->setItem(49, $quickCraft);
        $recipeBook = VanillaItems::BOOK();
        $recipeBook->setCustomName("§aRecipe Book");
        $recipeBook->setLore([
            "§7Click to view all available recipes!",
            "",
            "§cComing Soon!"
        ]);
        $inventory->setItem(48, $recipeBook);
    }

    private function handleResultSlotClick(InvMenuTransaction $transaction, array $craftingSlots, int $resultSlot): InvMenuTransactionResult {
        $inventory = $transaction->getAction()->getInventory();
        $player = $transaction->getPlayer();
        $resultItem = $inventory->getItem($resultSlot);
        
        if ($resultItem->isNull()) {
            return $transaction->discard();
        }
        if (!$player->getInventory()->canAddItem($resultItem)) {
            $player->sendMessage("§cYour inventory is full!");
            return $transaction->discard();
        }
        $player->getInventory()->addItem($resultItem);
        $this->consumeCraftingMaterials($inventory, $craftingSlots);
        $inventory->setItem($resultSlot, VanillaItems::AIR());
        $player->getNetworkSession()->getInvManager()->syncContents($player->getInventory());
        $player->getNetworkSession()->getInvManager()->syncContents($inventory);
        $this->updateCraftingResult($player, $inventory);
        
        return $transaction->discard();
    }

    private function consumeCraftingMaterials(\pocketmine\inventory\Inventory $inventory, array $craftingSlots): void {
        foreach ($craftingSlots as $slot) {
            $item = $inventory->getItem($slot);
            if (!$item->isNull()) {
                $item->setCount($item->getCount() - 1);
                $inventory->setItem($slot, $item->getCount() > 0 ? $item : VanillaItems::AIR());
            }
        }
    }

    private function updateCraftingResult(Player $player, \pocketmine\inventory\Inventory $inventory): void {
        $craftingGrid = [];
        $craftingSlots = [11, 12, 13, 20, 21, 22, 29, 30, 31];
        for ($i = 0; $i < 9; $i++) {
            $item = $inventory->getItem($craftingSlots[$i]);
            $craftingGrid[] = $item->isNull() ? null : $item;
        }
        $result = $this->getRecipeResult($craftingGrid);
        $inventory->setItem(25, $result ?? VanillaItems::AIR());
        $player->getNetworkSession()->getInvManager()->syncContents($inventory);
    }

    private function getRecipeResult(array $craftingGrid): ?Item {
        $recipes = $this->recipeRegistry->getAllRecipes();
        
        foreach ($recipes as $recipe) {
            if ($recipe instanceof MultiPatternRecipe) {
                foreach ($recipe->getPatterns() as $pattern) {
                    if ($this->matchesRecipeSimple($craftingGrid, $pattern)) {
                        return $recipe->getResult();
                    }
                }
            } else {
                if ($this->matchesRecipeSimple($craftingGrid, $recipe->getPattern())) {
                    return $recipe->getResult();
                }
            }
        }
        return null;
    }

    private function matchesRecipeSimple(array $craftingGrid, array $pattern): bool {
        $gridItems = $this->normalizeGrid($craftingGrid);
        $patternItems = $this->normalizePattern($pattern);
        return $this->arraysMatch($gridItems, $patternItems);
    }

    private function normalizeGrid(array $craftingGrid): array {
        $grid = [];
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $item = $craftingGrid[$i * 3 + $j];
                $grid[$i][$j] = ($item === null || $item->isNull()) ? null : $item->getTypeId();
            }
        }
        
        return $this->trimGrid($grid);
    }

    private function normalizePattern(array $pattern): array {
        $grid = [];
        for ($i = 0; $i < count($pattern); $i++) {
            for ($j = 0; $j < count($pattern[$i]); $j++) {
                $item = $pattern[$i][$j];
                $grid[$i][$j] = ($item === null) ? null : $item->getTypeId();
            }
        }
        
        return $this->trimGrid($grid);
    }

    private function trimGrid(array $grid): array {
        $minRow = 3; $maxRow = -1;
        $minCol = 3; $maxCol = -1;
        
        for ($i = 0; $i < count($grid); $i++) {
            for ($j = 0; $j < count($grid[$i]); $j++) {
                if ($grid[$i][$j] !== null) {
                    $minRow = min($minRow, $i);
                    $maxRow = max($maxRow, $i);
                    $minCol = min($minCol, $j);
                    $maxCol = max($maxCol, $j);
                }
            }
        }
        
        if ($minRow > $maxRow) {
            return [];
        }
        
        $result = [];
        for ($i = $minRow; $i <= $maxRow; $i++) {
            $row = [];
            for ($j = $minCol; $j <= $maxCol; $j++) {
                $row[] = $grid[$i][$j] ?? null;
            }
            $result[] = $row;
        }
        
        return $result;
    }

    private function arraysMatch(array $grid1, array $grid2): bool {
        if (count($grid1) !== count($grid2)) {
            return false;
        }
        
        for ($i = 0; $i < count($grid1); $i++) {
            if (count($grid1[$i]) !== count($grid2[$i])) {
                return false;
            }
            
            for ($j = 0; $j < count($grid1[$i]); $j++) {
                if ($grid1[$i][$j] !== $grid2[$i][$j]) {
                    return false;
                }
            }
        }
        
        return true;
    }
}