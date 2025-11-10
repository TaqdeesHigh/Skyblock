<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class CoalMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Coal Minion");
        $this->setDescription([
            "§7An automated minion that mines coal.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Coal + 1x Wooden Pickaxe"
        ]);

        $coal = VanillaItems::COAL();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $coal->setCount(16);

        $this->setPattern([
            [clone $coal, clone $coal, clone $coal],
            [clone $coal, clone $woodenPickaxe, clone $coal],
            [clone $coal, clone $coal, clone $coal]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Coal Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eCoal",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic coal mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "coal");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}