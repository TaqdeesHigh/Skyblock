<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\blocks;

use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class StoneBricksRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Stone Bricks");
        $this->setDescription([
            "§7Decorative building block.",
            "§7Made from stone."
        ]);

        $this->setPattern([
            [VanillaBlocks::STONE()->asItem(), VanillaBlocks::STONE()->asItem(), null],
            [VanillaBlocks::STONE()->asItem(), VanillaBlocks::STONE()->asItem(), null],
            [null, null, null]
        ]);

        $this->setResult(VanillaBlocks::STONE_BRICKS()->asItem()->setCount(4));
    }
}