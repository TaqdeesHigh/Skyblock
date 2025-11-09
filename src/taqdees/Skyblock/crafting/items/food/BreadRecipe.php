<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\food;

use pocketmine\item\VanillaItems;
use taqdees\Skyblock\crafting\Recipe;

class BreadRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Bread");
        $this->setDescription([
            "§7A basic food item.",
            "§7Restores hunger."
        ]);

        $this->setPattern([
            [VanillaItems::WHEAT(), VanillaItems::WHEAT(), VanillaItems::WHEAT()],
            [null, null, null],
            [null, null, null]
        ]);

        $this->setResult(VanillaItems::BREAD());
    }
}