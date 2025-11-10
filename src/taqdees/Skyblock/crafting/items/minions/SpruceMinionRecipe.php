<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\minions;

use taqdees\Skyblock\crafting\Recipe;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class SpruceMinionRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Spruce Minion");
        $this->setDescription([
            "§7An automated minion that collects spruce logs.",
            "§7Place on your island to start collecting!",
            "",
            "§eRequires: §7128x Spruce Log + 1x Wooden Axe"
        ]);

        $spruceLog = VanillaBlocks::SPRUCE_LOG()->asItem();
        $woodenAxe = VanillaItems::WOODEN_AXE();
        
        $spruceLog->setCount(16);

        $this->setPattern([
            [clone $spruceLog, clone $spruceLog, clone $spruceLog],
            [clone $spruceLog, clone $woodenAxe, clone $spruceLog],
            [clone $spruceLog, clone $spruceLog, clone $spruceLog]
        ]);

        $result = VanillaItems::VILLAGER_SPAWN_EGG();
        $result->setCustomName("§6Spruce Minion §7(Level 1)");
        $result->setLore([
            "§7Type: §eSpruce",
            "§7Level: §a1",
            "",
            "§7Place this minion on your island",
            "§7to start automatic spruce log collection!",
            "",
            "§eRight-click to place!"
        ]);
        
        $nbt = $result->getNamedTag();
        $nbt->setString("minion_type", "spruce");
        $nbt->setInt("minion_level", 1);
        $nbt->setString("minion_egg", "true");

        $this->setResult($result);
    }
}