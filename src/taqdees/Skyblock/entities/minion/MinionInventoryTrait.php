<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\item\Item;
use pocketmine\player\Player;

trait MinionInventoryTrait {
    protected array $minionInventory = [];
    protected int $maxInventorySlots = 15;
    protected bool $inventoryChanged = false;

    public function getMinionInventory(): array {
        return $this->minionInventory;
    }

    public function getMaxInventorySlots(): int {
        return min($this->level * 2, $this->maxInventorySlots);
    }

    public function hasInventorySpace(): bool {
        return count($this->minionInventory) < $this->getMaxInventorySlots();
    }

    public function getInventoryItemCount(): int {
        return count($this->minionInventory);
    }

    public function isInventoryFull(): bool {
        return !$this->hasInventorySpace();
    }

    public function addItemToInventory(Item $item): bool {
        if ($item->isNull() || $item->getCount() <= 0) {
            return false;
        }

        $slotsAvailable = $this->getMaxInventorySlots();
        foreach ($this->minionInventory as $key => $inventoryItem) {
            if ($inventoryItem->equals($item, true, false)) {
                $maxStack = $inventoryItem->getMaxStackSize();
                $currentCount = $inventoryItem->getCount();
                $itemCount = $item->getCount();
                
                if ($currentCount + $itemCount <= $maxStack) {
                    $inventoryItem->setCount($currentCount + $itemCount);
                    $this->markInventoryChanged();
                    return true;
                } else {
                    $canAdd = $maxStack - $currentCount;
                    if ($canAdd > 0) {
                        $inventoryItem->setCount($maxStack);
                        $item->setCount($itemCount - $canAdd);
                        $this->markInventoryChanged();
                    }
                }
            }
        }
        if (count($this->minionInventory) < $slotsAvailable && $item->getCount() > 0) {
            $this->minionInventory[] = clone $item;
            $this->markInventoryChanged();
            return true;
        }
        
        return false;
    }

    public function collectItemsFromInventory(Player $player): int {
        $collected = 0;
        $itemsToRemove = [];
        
        foreach ($this->minionInventory as $key => $item) {
            if ($player->getInventory()->canAddItem($item)) {
                $player->getInventory()->addItem($item);
                $itemsToRemove[] = $key;
                $collected++;
            } else {
                break;
            }
        }

        foreach (array_reverse($itemsToRemove) as $key) {
            unset($this->minionInventory[$key]);
        }
        $this->minionInventory = array_values($this->minionInventory);
        
        if ($collected > 0) {
            $this->markInventoryChanged();
        }
        
        return $collected;
    }

    protected function markInventoryChanged(): void {
        $this->inventoryChanged = true;
        $this->saveToFile();
    }

    protected function handleAutoSave(int $currentTick): void {
        if ($currentTick % 6000 === 0 && $this->inventoryChanged) {
            $this->saveToFile();
        }
    }
}