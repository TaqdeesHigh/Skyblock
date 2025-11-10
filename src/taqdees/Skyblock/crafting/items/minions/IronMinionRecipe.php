<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class IronMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Iron Minion");
        $this->setDescription([
            "§7An automated minion that mines iron.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Raw Iron + 1x Wooden Pickaxe"
        ]);

        $rawIron = VanillaItems::RAW_IRON();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $rawIron->setCount(16);

        $this->setPattern([
            [clone $rawIron, clone $rawIron, clone $rawIron],
            [clone $rawIron, clone $woodenPickaxe, clone $rawIron],
            [clone $rawIron, clone $rawIron, clone $rawIron]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Iron Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eIron",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic iron mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "iron");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}