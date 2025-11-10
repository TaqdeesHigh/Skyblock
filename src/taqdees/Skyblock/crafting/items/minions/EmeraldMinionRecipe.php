<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class EmeraldMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Emerald Minion");
        $this->setDescription([
            "§7An automated minion that mines emeralds.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Emerald + 1x Wooden Pickaxe"
        ]);

        $emerald = VanillaItems::EMERALD();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $emerald->setCount(16);

        $this->setPattern([
            [clone $emerald, clone $emerald, clone $emerald],
            [clone $emerald, clone $woodenPickaxe, clone $emerald],
            [clone $emerald, clone $emerald, clone $emerald]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Emerald Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eEmerald",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic emerald mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "emerald");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}