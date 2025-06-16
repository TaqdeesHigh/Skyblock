<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting;

use taqdees\Skyblock\crafting\items\StickRecipe;
use taqdees\Skyblock\crafting\items\WorkbenchRecipe;
use taqdees\Skyblock\crafting\items\ChestRecipe;

class RecipeRegistry {
    private array $recipes = [];
    private static ?RecipeRegistry $instance = null;

    private function __construct() {
        $this->registerRecipes();
    }

    public static function getInstance(): RecipeRegistry {
        if (self::$instance === null) {
            self::$instance = new RecipeRegistry();
        }
        return self::$instance;
    }

    private function registerRecipes(): void {
        $this->addRecipe(new StickRecipe());
        $this->addRecipe(new WorkbenchRecipe());
        $this->addRecipe(new ChestRecipe());
        
    }

    private function addRecipe(Recipe $recipe): void {
        $this->recipes[] = $recipe;
    }

    public function getAllRecipes(): array {
        return $this->recipes;
    }

    public function getRecipeByName(string $name): ?Recipe {
        foreach ($this->recipes as $recipe) {
            if ($recipe->getName() === $name) {
                return $recipe;
            }
        }
        return null;
    }
}