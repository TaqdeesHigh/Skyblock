<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\wooden;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class WoodenAxeRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Wooden Axe");
        $this->setDescription([
            "§7A basic chopping tool.",
            "§7Used to chop wood faster."
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaItems::STICK(), null],
            [null, VanillaItems::STICK(), null]
        ]);

        $this->setResult(VanillaItems::WOODEN_AXE());
    }
}