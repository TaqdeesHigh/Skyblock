<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\wooden;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class WoodenShovelRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Wooden Shovel");
        $this->setDescription([
            "§7A basic digging tool.",
            "§7Used to dig dirt and sand."
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [VanillaItems::STICK(), null, null],
            [VanillaItems::STICK(), null, null]
        ]);

        $this->setResult(VanillaItems::WOODEN_SHOVEL());
    }
}