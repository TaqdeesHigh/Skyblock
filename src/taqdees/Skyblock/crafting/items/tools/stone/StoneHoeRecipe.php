<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\stone;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class StoneHoeRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Stone Hoe");
        $this->setDescription([
            "§7An improved farming tool.",
            "§7Used to till farmland."
        ]);

        $this->setPattern([
            [VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem(), null],
            [null, VanillaItems::STICK(), null],
            [null, VanillaItems::STICK(), null]
        ]);

        $this->setResult(VanillaItems::STONE_HOE());
    }
}