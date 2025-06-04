<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions\professions;

use pocketmine\item\VanillaItems;

class FishingProfession extends Profession {
    
    public function __construct() {
        parent::__construct("Fishing", "§b");
    }
    
    protected function initializeTools(): void {
        $this->tools = [
            VanillaItems::FISHING_ROD()
        ];
    }
}