<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting\items\blocks;

use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use taqdees\Skyblock\crafting\Recipe;

class LadderRecipe extends Recipe {
    
    protected function initializeRecipe(): void {
        $this->setName("Ladder");
        $this->setDescription([
            "§7Used for climbing.",
            "§7Essential for vertical movement."
        ]);

        $this->setPattern([
            [VanillaItems::STICK(), null, VanillaItems::STICK()],
            [VanillaItems::STICK(), VanillaItems::STICK(), VanillaItems::STICK()],
            [VanillaItems::STICK(), null, VanillaItems::STICK()]
        ]);

        $this->setResult(VanillaBlocks::LADDER()->asItem()->setCount(3));
    }
}