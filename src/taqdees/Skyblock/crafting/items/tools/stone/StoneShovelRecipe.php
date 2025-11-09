<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\tools\stone;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class StoneShovelRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Stone Shovel");
        $this->setDescription([
            "§7An improved digging tool.",
            "§7Faster than wooden tools."
        ]);

        $this->setPattern([
            [VanillaBlocks::COBBLESTONE()->asItem(), null, null],
            [VanillaItems::STICK(), null, null],
            [VanillaItems::STICK(), null, null]
        ]);

        $this->setResult(VanillaItems::STONE_SHOVEL());
    }
}