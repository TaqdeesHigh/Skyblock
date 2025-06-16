<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class WheatMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Wheat Minion");
        $this->setDescription([
            "§7A automated minion that farms wheat.",
            "§7Place on your island to start farming!",
            "",
            "§eRequires: §7128x Wheat + 1x Wooden Hoe"
        ]);

        $wheat = VanillaItems::WHEAT();
        $woodenHoe = VanillaItems::WOODEN_HOE();
        
        $wheat->setCount(16);

        $this->setPattern([
            [clone $wheat, clone $wheat, clone $wheat],
            [clone $wheat, clone $woodenHoe, clone $wheat],
            [clone $wheat, clone $wheat, clone $wheat]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Wheat Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eWheat",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic wheat farming!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "wheat");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}