<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class MelonMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Melon Minion");
        $this->setDescription([
            "§7An automated minion that farms melons.",
            "§7Place on your island to start farming!",
            "",
            "§eRequires: §7128x Melon Slice + 1x Wooden Hoe"
        ]);

        $melon = VanillaItems::MELON();
        $woodenHoe = VanillaItems::WOODEN_HOE();
        
        $melon->setCount(16);

        $this->setPattern([
            [clone $melon, clone $melon, clone $melon],
            [clone $melon, clone $woodenHoe, clone $melon],
            [clone $melon, clone $melon, clone $melon]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Melon Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eMelon",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic melon farming!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "melon");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}