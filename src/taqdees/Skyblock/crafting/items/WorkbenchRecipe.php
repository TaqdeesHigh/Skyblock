<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items;

use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class WorkbenchRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Crafting Table");
        $this->setDescription([
            "§7Used for crafting items.",
            "§7Essential for survival!"
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [null, null, null]
        ]);

        $this->setResult(VanillaBlocks::CRAFTING_TABLE()->asItem());
    }
}