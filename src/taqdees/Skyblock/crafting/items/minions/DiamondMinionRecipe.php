<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class DiamondMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Diamond Minion");
        $this->setDescription([
            "§7An automated minion that mines diamonds.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Diamond + 1x Wooden Pickaxe"
        ]);

        $diamond = VanillaItems::DIAMOND();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $diamond->setCount(16);

        $this->setPattern([
            [clone $diamond, clone $diamond, clone $diamond],
            [clone $diamond, clone $woodenPickaxe, clone $diamond],
            [clone $diamond, clone $diamond, clone $diamond]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Diamond Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eDiamond",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic diamond mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "diamond");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}