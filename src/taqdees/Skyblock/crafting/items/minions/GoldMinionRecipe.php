<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class GoldMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Gold Minion");
        $this->setDescription([
            "§7An automated minion that mines gold.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Raw Gold + 1x Wooden Pickaxe"
        ]);

        $rawGold = VanillaItems::RAW_GOLD();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $rawGold->setCount(16);

        $this->setPattern([
            [clone $rawGold, clone $rawGold, clone $rawGold],
            [clone $rawGold, clone $woodenPickaxe, clone $rawGold],
            [clone $rawGold, clone $rawGold, clone $rawGold]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Gold Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eGold",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic gold mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "gold");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}