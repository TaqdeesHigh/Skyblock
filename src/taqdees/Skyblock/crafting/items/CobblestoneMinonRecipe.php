<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class CobblestoneMinonRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Cobblestone Minion");
        $this->setDescription([
            "§7A automated minion that collects cobblestone.",
            "§7Place on your island to start collecting!",
            "",
            "§eRequires: §7128x Cobblestone + 1x Wooden Pickaxe"
        ]);

        $cobblestone = VanillaBlocks::COBBLESTONE()->asItem();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $cobblestone->setCount(16);

        $this->setPattern([
            [clone $cobblestone, clone $cobblestone, clone $cobblestone],
            [clone $cobblestone, clone $woodenPickaxe, clone $cobblestone],
            [clone $cobblestone, clone $cobblestone, clone $cobblestone]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Cobblestone Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eCobblestone",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic cobblestone collection!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "cobblestone");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}