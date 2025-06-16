<?php

declare(strict_types=1);

namespace taqdees\Skyblock\crafting;

use pocketmine\item\Item;

abstract class Recipe {
    protected array $pattern;
    protected Item $result;
    protected string $name;
    protected array $description;

    public function __construct() {
        $this->initializeRecipe();
    }

    abstract protected function initializeRecipe(): void;

    public function getPattern(): array {
        return $this->pattern;
    }

    public function getResult(): Item {
        return clone $this->result;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): array {
        return $this->description;
    }

    protected function setPattern(array $pattern): void {
        $this->pattern = $pattern;
    }

    protected function setResult(Item $result): void {
        $this->result = $result;
    }

    protected function setName(string $name): void {
        $this->name = $name;
    }

    protected function setDescription(array $description): void {
        $this->description = $description;
    }
}