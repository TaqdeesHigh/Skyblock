<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\utilities;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class BowlRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Bowl");
        $this->setDescription([
            "§7Used to hold food.",
            "§7Required for soups and stews."
        ]);

        $this->setPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, VanillaBlocks::OAK_PLANKS()->asItem()],
            [null, VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [null, null, null]
        ]);

        $this->setResult(VanillaItems::BOWL()->setCount(4));
    }
}