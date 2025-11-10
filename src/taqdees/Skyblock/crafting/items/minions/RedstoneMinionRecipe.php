<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class RedstoneMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Redstone Minion");
        $this->setDescription([
            "§7An automated minion that mines redstone.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Redstone Dust + 1x Wooden Pickaxe"
        ]);

        $redstone = VanillaItems::REDSTONE_DUST();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $redstone->setCount(16);

        $this->setPattern([
            [clone $redstone, clone $redstone, clone $redstone],
            [clone $redstone, clone $woodenPickaxe, clone $redstone],
            [clone $redstone, clone $redstone, clone $redstone]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Redstone Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eRedstone",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic redstone mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "redstone");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}