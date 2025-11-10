<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class DarkOakMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Dark Oak Minion");
        $this->setDescription([
            "§7An automated minion that collects dark oak logs.",
            "§7Place on your island to start collecting!",
            "",
            "§eRequires: §7128x Dark Oak Log + 1x Wooden Axe"
        ]);

        $darkOakLog = VanillaBlocks::DARK_OAK_LOG()->asItem();
        $woodenAxe = VanillaItems::WOODEN_AXE();
        
        $darkOakLog->setCount(16);

        $this->setPattern([
            [clone $darkOakLog, clone $darkOakLog, clone $darkOakLog],
            [clone $darkOakLog, clone $woodenAxe, clone $darkOakLog],
            [clone $darkOakLog, clone $darkOakLog, clone $darkOakLog]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Dark Oak Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eDark Oak",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic dark oak log collection!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "dark_oak");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}