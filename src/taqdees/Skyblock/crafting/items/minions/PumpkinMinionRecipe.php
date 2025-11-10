<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class PumpkinMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Pumpkin Minion");
        $this->setDescription([
            "§7An automated minion that farms pumpkins.",
            "§7Place on your island to start farming!",
            "",
            "§eRequires: §7128x Pumpkin + 1x Wooden Hoe"
        ]);

        $pumpkin = VanillaBlocks::PUMPKIN()->asItem();
        $woodenHoe = VanillaItems::WOODEN_HOE();
        
        $pumpkin->setCount(16);

        $this->setPattern([
            [clone $pumpkin, clone $pumpkin, clone $pumpkin],
            [clone $pumpkin, clone $woodenHoe, clone $pumpkin],
            [clone $pumpkin, clone $pumpkin, clone $pumpkin]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Pumpkin Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §ePumpkin",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic pumpkin farming!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "pumpkin");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}