<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\stone;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class StoneSwordRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Stone Sword");
        $this->setDescription([
            "§7An improved combat weapon.",
            "§7Stronger than wooden weapons."
        ]);

        $this->setPattern([
            [VanillaBlocks::COBBLESTONE()->asItem(), null, null],
            [VanillaBlocks::COBBLESTONE()->asItem(), null, null],
            [VanillaItems::STICK(), null, null]
        ]);

        $this->setResult(VanillaItems::STONE_SWORD());
    }
}