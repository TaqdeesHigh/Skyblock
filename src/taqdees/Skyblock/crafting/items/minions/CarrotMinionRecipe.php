<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class CarrotMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Carrot Minion");
        $this->setDescription([
            "§7An automated minion that farms carrots.",
            "§7Place on your island to start farming!",
            "",
            "§eRequires: §7128x Carrot + 1x Wooden Hoe"
        ]);

        $carrot = VanillaItems::CARROT();
        $woodenHoe = VanillaItems::WOODEN_HOE();
        
        $carrot->setCount(16);

        $this->setPattern([
            [clone $carrot, clone $carrot, clone $carrot],
            [clone $carrot, clone $woodenHoe, clone $carrot],
            [clone $carrot, clone $carrot, clone $carrot]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Carrot Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eCarrot",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic carrot farming!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "carrot");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}