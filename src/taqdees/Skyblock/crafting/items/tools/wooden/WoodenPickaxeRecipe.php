<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\wooden;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class WoodenPickaxeRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Wooden Pickaxe");
        $this->setDescription([
            "§7A basic mining tool.",
            "§7Used to mine stone and ores."
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem()],
            [null, VanillaItems::STICK(), null],
            [null, VanillaItems::STICK(), null]
        ]);

        $this->setResult(VanillaItems::WOODEN_PICKAXE());
    }
}