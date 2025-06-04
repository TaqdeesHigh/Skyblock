<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions\professions;

use pocketmine\item\VanillaItems;

class MiningProfession extends Profession {
    
    public function __construct() {
        parent::__construct("Mining", "§7");
    }
    
    protected function initializeTools(): void {
        $this->tools = [
            VanillaItems::WOODEN_PICKAXE(),
            VanillaItems::STONE_PICKAXE(),
            VanillaItems::IRON_PICKAXE(),
            VanillaItems::DIAMOND_PICKAXE(),
            VanillaItems::NETHERITE_PICKAXE()
        ];
    }
}