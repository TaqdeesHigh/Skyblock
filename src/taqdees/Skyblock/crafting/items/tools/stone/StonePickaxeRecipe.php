<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\stone;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class StonePickaxeRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Stone Pickaxe");
        $this->setDescription([
            "§7An improved mining tool.",
            "§7Faster than wooden tools."
        ]);

        $this->setPattern([
            [VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem(), VanillaBlocks::COBBLESTONE()->asItem()],
            [null, VanillaItems::STICK(), null],
            [null, VanillaItems::STICK(), null]
        ]);

        $this->setResult(VanillaItems::STONE_PICKAXE());
    }
}