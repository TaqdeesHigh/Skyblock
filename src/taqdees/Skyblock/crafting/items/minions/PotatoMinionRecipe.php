<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;

class PotatoMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Potato Minion");
        $this->setDescription([
            "§7An automated minion that farms potatoes.",
            "§7Place on your island to start farming!",
            "",
            "§eRequires: §7128x Potato + 1x Wooden Hoe"
        ]);

        $potato = VanillaItems::POTATO();
        $woodenHoe = VanillaItems::WOODEN_HOE();
        
        $potato->setCount(16);

        $this->setPattern([
            [clone $potato, clone $potato, clone $potato],
            [clone $potato, clone $woodenHoe, clone $potato],
            [clone $potato, clone $potato, clone $potato]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Potato Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §ePotato",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic potato farming!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "potato");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}