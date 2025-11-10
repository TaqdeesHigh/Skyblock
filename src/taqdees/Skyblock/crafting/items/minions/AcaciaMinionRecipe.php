<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class AcaciaMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Acacia Minion");
        $this->setDescription([
            "§7An automated minion that collects acacia logs.",
            "§7Place on your island to start collecting!",
            "",
            "§eRequires: §7128x Acacia Log + 1x Wooden Axe"
        ]);

        $acaciaLog = VanillaBlocks::ACACIA_LOG()->asItem();
        $woodenAxe = VanillaItems::WOODEN_AXE();
        
        $acaciaLog->setCount(16);

        $this->setPattern([
            [clone $acaciaLog, clone $acaciaLog, clone $acaciaLog],
            [clone $acaciaLog, clone $woodenAxe, clone $acaciaLog],
            [clone $acaciaLog, clone $acaciaLog, clone $acaciaLog]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Acacia Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eAcacia",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic acacia log collection!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "acacia");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}