<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items;

use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class ChestRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Chest");
        $this->setDescription([
            "§7A container for storing items.",
            "§7Holds up to 27 stacks!"
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem()],
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, VanillaBlocks::OAK_PLANKS()->asItem()],
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem()]
        ]);

        $this->setResult(VanillaBlocks::CHEST()->asItem());
    }
}