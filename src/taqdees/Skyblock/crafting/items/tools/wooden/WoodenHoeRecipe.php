<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\wooden;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class WoodenHoeRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Wooden Hoe");
        $this->setDescription([
            "§7A basic farming tool.",
            "§7Used to till farmland."
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [null, VanillaItems::STICK(), null],
            [null, VanillaItems::STICK(), null]
        ]);

        $this->setResult(VanillaItems::WOODEN_HOE());
    }
}