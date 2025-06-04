<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions\professions;

use pocketmine\item\VanillaItems;

class FarmingProfession extends Profession {
    
    public function __construct() {
        parent::__construct("Farming", "§a");
    }
    
    protected function initializeTools(): void {
        $this->tools = [
            VanillaItems::WOODEN_HOE(),
            VanillaItems::STONE_HOE(),
            VanillaItems::IRON_HOE(),
            VanillaItems::DIAMOND_HOE(),
            VanillaItems::NETHERITE_HOE()
        ];
    }
}