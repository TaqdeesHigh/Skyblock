<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\utilities;

use pocketmine\item\VanillaItems;
use taqdees\Skyblock\crafting\Recipe;

class BucketRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Bucket");
        $this->setDescription([
            "§7Used to carry liquids.",
            "§7Can hold water, lava, or milk."
        ]);

        $this->setPattern([
            [VanillaItems::IRON_INGOT(), null, VanillaItems::IRON_INGOT()],
            [null, VanillaItems::IRON_INGOT(), null],
            [null, null, null]
        ]);

        $this->setResult(VanillaItems::BUCKET());
    }
}