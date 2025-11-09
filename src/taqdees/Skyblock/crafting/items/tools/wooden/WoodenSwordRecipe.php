<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\wooden;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class WoodenSwordRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Wooden Sword");
        $this->setDescription([
            "§7A basic combat weapon.",
            "§7Used to fight mobs."
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [VanillaItems::STICK(), null, null]
        ]);

        $this->setResult(VanillaItems::WOODEN_SWORD());
    }
}