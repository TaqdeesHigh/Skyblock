<?php

declare(strict_types=1);

namespace taqdees\Skyblock\minions\professions;

use pocketmine\item\Item;

abstract class Profession {
    
    protected string $name;
    protected string $color;
    protected array $tools = [];
    
    public function __construct(string $name, string $color) {
        $this->name = $name;
        $this->color = $color;
        $this->initializeTools();
    }
    
    abstract protected function initializeTools(): void;
    
    public function getName(): string {
        return $this->name;
    }
    
    public function getColor(): string {
        return $this->color;
    }
    
    public function getDisplayName(): string {
        return $this->color . $this->name;
    }
    
    public function getTools(): array {
        return $this->tools;
    }
    
    public function getPrimaryTool(): ?Item {
        return $this->tools[0] ?? null;
    }
}