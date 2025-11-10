<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class BirchMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Birch Minion");
        $this->setDescription([
            "§7An automated minion that collects birch logs.",
            "§7Place on your island to start collecting!",
            "",
            "§eRequires: §7128x Birch Log + 1x Wooden Axe"
        ]);

        $birchLog = VanillaBlocks::BIRCH_LOG()->asItem();
        $woodenAxe = VanillaItems::WOODEN_AXE();
        
        $birchLog->setCount(16);

        $this->setPattern([
            [clone $birchLog, clone $birchLog, clone $birchLog],
            [clone $birchLog, clone $woodenAxe, clone $birchLog],
            [clone $birchLog, clone $birchLog, clone $birchLog]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Birch Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eBirch",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic birch log collection!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "birch");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}