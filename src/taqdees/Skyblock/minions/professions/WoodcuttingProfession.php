<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions\professions;

use pocketmine\item\VanillaItems;

class WoodcuttingProfession extends Profession {
    
    public function __construct() {
        parent::__construct("Woodcutting", "§6");
    }
    
    protected function initializeTools(): void {
        $this->tools = [
            VanillaItems::WOODEN_AXE(),
            VanillaItems::STONE_AXE(),
            VanillaItems::IRON_AXE(),
            VanillaItems::DIAMOND_AXE(),
            VanillaItems::NETHERITE_AXE()
        ];
    }
}