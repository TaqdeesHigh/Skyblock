<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\materials;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\MultiPatternRecipe;

class StickRecipe extends MultiPatternRecipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Stick");
        $this->setDescription([
            "§7A basic crafting material",
            "§7used in many recipes."
        ]);

        $this->addPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), VanillaBlocks::OAK_PLANKS()->asItem(), null],
            [null, null, null],
            [null, null, null]
        ]);
        $this->addPattern([
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [VanillaBlocks::OAK_PLANKS()->asItem(), null, null],
            [null, null, null]
        ]);

        $this->setResult(VanillaItems::STICK()->setCount(4));
    }
}