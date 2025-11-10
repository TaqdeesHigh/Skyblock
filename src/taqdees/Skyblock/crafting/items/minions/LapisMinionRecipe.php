<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class LapisMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Lapis Minion");
        $this->setDescription([
            "§7An automated minion that mines lapis lazuli.",
            "§7Place on your island to start mining!",
            "",
            "§eRequires: §7128x Lapis Lazuli + 1x Wooden Pickaxe"
        ]);

        $lapis = VanillaItems::LAPIS_LAZULI();
        $woodenPickaxe = VanillaItems::WOODEN_PICKAXE();
        
        $lapis->setCount(16);

        $this->setPattern([
            [clone $lapis, clone $lapis, clone $lapis],
            [clone $lapis, clone $woodenPickaxe, clone $lapis],
            [clone $lapis, clone $lapis, clone $lapis]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Lapis Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eLapis",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic lapis mining!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "lapis");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}