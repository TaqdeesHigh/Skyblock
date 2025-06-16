<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting;

use pocketmine\item\Item;

abstract class MultiPatternRecipe extends Recipe {
    protected array $patterns = [];

    public function getPatterns(): array {
        return $this->patterns;
    }

    protected function addPattern(array $pattern): void {
        $this->patterns[] = $pattern;
    }

    public function getPattern(): array {
        return $this->patterns[0] ?? [];
    }
}