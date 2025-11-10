<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class OakMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Oak Minion");
        $this->setDescription([
            "§7An automated minion that collects oak logs.",
            "§7Place on your island to start collecting!",
            "",
            "§eRequires: §7128x Oak Log + 1x Wooden Axe"
        ]);

        $oakLog = VanillaBlocks::OAK_LOG()->asItem();
        $woodenAxe = VanillaItems::WOODEN_AXE();
        
        $oakLog->setCount(16);

        $this->setPattern([
            [clone $oakLog, clone $oakLog, clone $oakLog],
            [clone $oakLog, clone $woodenAxe, clone $oakLog],
            [clone $oakLog, clone $oakLog, clone $oakLog]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Oak Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eOak",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic oak log collection!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "oak");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}