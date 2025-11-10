<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting;

use taqdees\Skyblock\crafting\items\materials\StickRecipe;
use taqdees\Skyblock\crafting\items\blocks\ChestRecipe;
use taqdees\Skyblock\crafting\items\blocks\WorkbenchRecipe;
use taqdees\Skyblock\crafting\items\blocks\FurnaceRecipe;
use taqdees\Skyblock\crafting\items\blocks\LadderRecipe;
use taqdees\Skyblock\crafting\items\blocks\StoneBricksRecipe;
use taqdees\Skyblock\crafting\items\blocks\TorchRecipe;
use taqdees\Skyblock\crafting\items\tools\wooden\WoodenPickaxeRecipe;
use taqdees\Skyblock\crafting\items\tools\wooden\WoodenAxeRecipe;
use taqdees\Skyblock\crafting\items\tools\wooden\WoodenShovelRecipe;
use taqdees\Skyblock\crafting\items\tools\wooden\WoodenHoeRecipe;
use taqdees\Skyblock\crafting\items\tools\wooden\WoodenSwordRecipe;
use taqdees\Skyblock\crafting\items\tools\stone\StonePickaxeRecipe;
use taqdees\Skyblock\crafting\items\tools\stone\StoneAxeRecipe;
use taqdees\Skyblock\crafting\items\tools\stone\StoneShovelRecipe;
use taqdees\Skyblock\crafting\items\tools\stone\StoneHoeRecipe;
use taqdees\Skyblock\crafting\items\tools\stone\StoneSwordRecipe;
use taqdees\Skyblock\crafting\items\utilities\BucketRecipe;
use taqdees\Skyblock\crafting\items\utilities\BowlRecipe;
use taqdees\Skyblock\crafting\items\food\BreadRecipe;
use taqdees\Skyblock\crafting\items\minions\CobblestoneMinonRecipe;
use taqdees\Skyblock\crafting\items\minions\CoalMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\IronMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\GoldMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\DiamondMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\EmeraldMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\LapisMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\RedstoneMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\WheatMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\CarrotMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\PotatoMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\MelonMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\PumpkinMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\OakMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\SpruceMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\BirchMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\AcaciaMinionRecipe;
use taqdees\Skyblock\crafting\items\minions\DarkOakMinionRecipe;

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
        $this->addRecipe(new FurnaceRecipe());
        $this->addRecipe(new LadderRecipe());
        $this->addRecipe(new TorchRecipe());
        $this->addRecipe(new StoneBricksRecipe());
        
        $this->addRecipe(new WoodenPickaxeRecipe());
        $this->addRecipe(new WoodenAxeRecipe());
        $this->addRecipe(new WoodenShovelRecipe());
        $this->addRecipe(new WoodenHoeRecipe());
        $this->addRecipe(new WoodenSwordRecipe());
        
        $this->addRecipe(new StonePickaxeRecipe());
        $this->addRecipe(new StoneAxeRecipe());
        $this->addRecipe(new StoneShovelRecipe());
        $this->addRecipe(new StoneHoeRecipe());
        $this->addRecipe(new StoneSwordRecipe());
        
        $this->addRecipe(new BucketRecipe());
        $this->addRecipe(new BowlRecipe());
        
        $this->addRecipe(new BreadRecipe());
        
        $this->addRecipe(new CobblestoneMinonRecipe());
        $this->addRecipe(new CoalMinionRecipe());
        $this->addRecipe(new IronMinionRecipe());
        $this->addRecipe(new GoldMinionRecipe());
        $this->addRecipe(new DiamondMinionRecipe());
        $this->addRecipe(new EmeraldMinionRecipe());
        $this->addRecipe(new LapisMinionRecipe());
        $this->addRecipe(new RedstoneMinionRecipe());
        
        $this->addRecipe(new WheatMinionRecipe());
        $this->addRecipe(new CarrotMinionRecipe());
        $this->addRecipe(new PotatoMinionRecipe());
        $this->addRecipe(new MelonMinionRecipe());
        $this->addRecipe(new PumpkinMinionRecipe());
        
        $this->addRecipe(new OakMinionRecipe());
        $this->addRecipe(new SpruceMinionRecipe());
        $this->addRecipe(new BirchMinionRecipe());
        $this->addRecipe(new AcaciaMinionRecipe());
        $this->addRecipe(new DarkOakMinionRecipe());
    }

    private function addRecipe(Recipe $recipe): void {
        if ($recipe->isValid()) {
            $this->recipes[] = $recipe;
        }
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