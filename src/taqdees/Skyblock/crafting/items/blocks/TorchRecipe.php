<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\blocks;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class TorchRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Torch");
        $this->setDescription([
            "§7Provides light.",
            "§7Essential for survival!"
        ]);

        $this->setPattern([
            [VanillaItems::COAL(), null, null],
            [VanillaItems::STICK(), null, null],
            [null, null, null]
        ]);

        $this->setResult(VanillaBlocks::TORCH()->asItem()->setCount(4));
    }
}