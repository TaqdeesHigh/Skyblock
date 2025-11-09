<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\blocks;

use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class FurnaceRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Furnace");
        $this->setDescription([
            "§7Used for smelting ores",
            "§7and cooking food."
        ]);

        $this->setPattern([
            [VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem()],
            [VanillaBlocks::COBBLESTONE()->asItem(), null, VanillaBlocks::COBBLESTONE()->asItem()],
            [VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem()]
        ]);

        $this->setResult(VanillaBlocks::FURNACE()->asItem());
    }
}