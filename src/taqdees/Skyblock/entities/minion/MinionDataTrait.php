<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;

trait MinionDataTrait {
    public function saveToFile(): void {
        try {
            $data = [
                'minionType' => $this->minionType,
                'level' => $this->level,
                'position' => [
                    'x' => $this->getPosition()->x,
                    'y' => $this->getPosition()->y,
                    'z' => $this->getPosition()->z,
                    'world' => $this->getWorld()->getFolderName()
                ],
                'inventory' => []
            ];

            foreach ($this->minionInventory as $slot => $item) {
                if (!$item->isNull() && $item->getCount() > 0) {
                    $data['inventory'][] = [
                        'id' => $item->getTypeId(),
                        'count' => $item->getCount(),
                        'name' => $item->getName(),
                        'nbt' => base64_encode((new \pocketmine\nbt\LittleEndianNbtSerializer())->write(new \pocketmine\nbt\TreeRoot($item->nbtSerialize())))
                    ];
                }
            }

            $minionsDir = $this->plugin->getDataFolder() . "minions/";
            if (!is_dir($minionsDir)) {
                mkdir($minionsDir, 0777, true);
            }
            
            $fileName = "minion_" . $this->minionType . "_" . floor($this->getPosition()->x) . "_" . floor($this->getPosition()->y) . "_" . floor($this->getPosition()->z) . ".json";
            $filePath = $minionsDir . $fileName;
            
            $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
            if ($result === false) {
                $this->plugin->getLogger()->error("Failed to write minion data to file: " . $filePath);
            } else {
                $this->plugin->getLogger()->debug("Saved minion data to: " . $filePath);
                $this->inventoryChanged = false;
            }
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to save minion data: " . $e->getMessage());
        }
    }

    public function loadFromFile(): void {
        try {
            $minionsDir = $this->plugin->getDataFolder() . "minions/";
            $fileName = "minion_" . $this->minionType . "_" . floor($this->getPosition()->x) . "_" . floor($this->getPosition()->y) . "_" . floor($this->getPosition()->z) . ".json";
            $filePath = $minionsDir . $fileName;
            
            if (!file_exists($filePath)) {
                $this->plugin->getLogger()->debug("No save file found for minion at: " . $filePath);
                return;
            }
            
            $content = file_get_contents($filePath);
            if ($content === false) {
                $this->plugin->getLogger()->error("Failed to read minion save file: " . $filePath);
                return;
            }
            
            $data = json_decode($content, true);
            if (!is_array($data)) {
                $this->plugin->getLogger()->error("Invalid JSON in minion save file: " . $filePath);
                return;
            }
            
            $this->level = $data['level'] ?? 1;
            $this->minionInventory = [];
            
            if (isset($data['inventory']) && is_array($data['inventory'])) {
                foreach ($data['inventory'] as $itemData) {
                    $item = $this->createItemFromData($itemData);
                    if ($item !== null && !$item->isNull()) {
                        $this->minionInventory[] = $item;
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to load minion data: " . $e->getMessage());
        }
    }

    private function createItemFromData(array $itemData): ?Item {
        try {
            $typeId = $itemData['id'] ?? 0;
            $count = $itemData['count'] ?? 1;
            $nbtData = $itemData['nbt'] ?? '';
            $name = $itemData['name'] ?? '';
            
            $item = null;
            if ($typeId < 0) {
                foreach (VanillaBlocks::getAll() as $block) {
                    if ($block->getTypeId() === $typeId) {
                        $item = $block->asItem();
                        break;
                    }
                }
            } else {
                foreach (VanillaItems::getAll() as $vanillaItem) {
                    if ($vanillaItem->getTypeId() === $typeId) {
                        $item = clone $vanillaItem;
                        break;
                    }
                }
                
                if ($item === null) {
                    foreach (VanillaBlocks::getAll() as $block) {
                        if ($block->getTypeId() === $typeId) {
                            $item = $block->asItem();
                            break;
                        }
                    }
                }
            }

            if ($item === null && !empty($name)) {
                try {
                    $parser = StringToItemParser::getInstance();
                    $item = $parser->parse($name);
                } catch (\Exception $e) {}
            }

            if ($item === null && $typeId === -10080) {
                $item = VanillaBlocks::COBBLESTONE()->asItem();
            }
            
            if ($item === null) {
                return null;
            }
            
            $item->setCount($count);
            if (!empty($nbtData)) {
                try {
                    $decodedNBT = base64_decode($nbtData);
                    if ($decodedNBT !== false) {
                        $reader = new \pocketmine\nbt\LittleEndianNbtSerializer();
                        $itemNBTTag = $reader->read($decodedNBT)->mustGetCompoundTag();
                        $item->nbtDeserialize($itemNBTTag);
                    }
                } catch (\Exception $e) {}
            }
            
            return $item;
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error creating item from data: " . $e->getMessage());
            return null;
        }
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("minionType", $this->minionType);
        $nbt->setString("customName", $this->customName);
        $nbt->setInt("level", $this->level);
        $nbt->setInt("workCooldown", $this->workCooldown);
        $nbt->setInt("breakTime", $this->breakTime);
        $nbt->setInt("breakCooldown", $this->breakCooldown);
        $nbt->setInt("lastBreakTick", $this->lastBreakTick);
        
        if ($this->profession !== null) {
            $nbt->setString("profession", $this->profession->getName());
        }
        
        if ($this->lockedPosition !== null) {
            $nbt->setFloat("lockedX", $this->lockedPosition->x);
            $nbt->setFloat("lockedY", $this->lockedPosition->y);
            $nbt->setFloat("lockedZ", $this->lockedPosition->z);
        }
        
        $inventoryList = new ListTag();
        foreach ($this->minionInventory as $slot => $item) {
            if ($item === null || $item->isNull() || $item->getCount() <= 0) {
                continue;
            }
            
            try {
                $itemData = new CompoundTag();
                $itemData->setInt("id", $item->getTypeId());
                $itemData->setInt("count", $item->getCount());
                $itemData->setInt("slot", $slot);
                $itemNBT = $item->nbtSerialize();
                $writer = new \pocketmine\nbt\LittleEndianNbtSerializer();
                $itemNBTBinary = $writer->write(new \pocketmine\nbt\TreeRoot($itemNBT));
                $itemData->setString("itemNBT", base64_encode($itemNBTBinary));
                
                $inventoryList->push($itemData);
            } catch (\Exception $e) {
                $this->plugin->getLogger()->warning("Failed to save minion inventory item: " . $e->getMessage());
            }
        }
        $nbt->setTag("MinionInventory", $inventoryList);
        
        return $nbt;
    }

    public function readSaveData(CompoundTag $nbt): void {
        parent::readSaveData($nbt);
        $this->minionType = $nbt->getString("minionType", "cobblestone");
        $this->customName = $nbt->getString("customName", ucfirst($this->minionType) . " Minion");
        $this->level = $nbt->getInt("level", 1);
        $this->workCooldown = $nbt->getInt("workCooldown", 20);
        $this->breakTime = $nbt->getInt("breakTime", 30);
        $this->breakCooldown = $nbt->getInt("breakCooldown", 100);
        $this->lastBreakTick = $nbt->getInt("lastBreakTick", 0);

        if ($nbt->hasTag("profession")) {
            $this->profession = ProfessionRegistry::get($nbt->getString("profession"));
        } else {
            $this->profession = $this->initializeProfession();
        }
        
        if ($nbt->hasTag("lockedX") && $nbt->hasTag("lockedY") && $nbt->hasTag("lockedZ")) {
            $this->lockedPosition = new Vector3(
                $nbt->getFloat("lockedX"),
                $nbt->getFloat("lockedY"),
                $nbt->getFloat("lockedZ")
            );
            $this->positionLocked = true;
        } else {
            $this->lockPosition();
        }

        $this->minionInventory = [];
        if ($nbt->hasTag("MinionInventory")) {
            $inventoryList = $nbt->getListTag("MinionInventory");
            foreach ($inventoryList as $itemTag) {
                if ($itemTag instanceof CompoundTag) {
                    try {
                        $typeId = $itemTag->getInt("id");
                        $count = $itemTag->getInt("count");
                        $itemNBTString = $itemTag->getString("itemNBT", "");
                        
                        $item = $this->createItemFromTypeId($typeId, $count);
                        if ($item !== null && !empty($itemNBTString)) {
                            try {
                                $decodedNBT = base64_decode($itemNBTString);
                                if ($decodedNBT !== false) {
                                    $reader = new \pocketmine\nbt\LittleEndianNbtSerializer();
                                    $itemNBTTag = $reader->read($decodedNBT)->mustGetCompoundTag();
                                    $item->nbtDeserialize($itemNBTTag);
                                }
                            } catch (\Exception $e) {
                                $this->plugin->getLogger()->warning("Failed to deserialize item NBT: " . $e->getMessage());
                            }
                        }
                        
                        if ($item !== null && !$item->isNull()) {
                            $this->minionInventory[] = $item;
                        }
                    } catch (\Exception $e) {
                        $this->plugin->getLogger()->warning("Failed to load minion inventory item: " . $e->getMessage());
                    }
                }
            }
        }

        $this->loadFromFile();
        $this->setNameTag($this->getDisplayName());
        $this->updateWorkStats();
        $this->updateEquipment();
        $this->setScale(0.6);
    }

    private function createItemFromTypeId(int $typeId, int $count): ?Item {
        try {
            $item = null;
            if ($typeId < 0) {
                foreach (VanillaBlocks::getAll() as $block) {
                    if ($block->getTypeId() === $typeId) {
                        $item = $block->asItem();
                        break;
                    }
                }
            } else {
                foreach (VanillaItems::getAll() as $vanillaItem) {
                    if ($vanillaItem->getTypeId() === $typeId) {
                        $item = clone $vanillaItem;
                        break;
                    }
                }
                if ($item === null) {
                    foreach (VanillaBlocks::getAll() as $block) {
                        if ($block->getTypeId() === $typeId) {
                            $item = $block->asItem();
                            break;
                        }
                    }
                }
            }
            
            if ($item === null) {
                return null;
            }
            
            $item->setCount($count);
            return $item;
            
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error creating item from type ID $typeId: " . $e->getMessage());
            return null;
        }
    }

    protected function onDispose(): void {
        if ($this->inventoryChanged || !empty($this->minionInventory)) {
            $this->saveToFile();
        }
        parent::onDispose();
    }
    
    public function onServerShutdown(): void {
        $this->saveToFile();
    }

    public function forceSave(): void {
        try {
            $nbt = $this->saveNBT();
            $this->plugin->getLogger()->info("Minion data saved for " . $this->getDisplayName());
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to save minion data: " . $e->getMessage());
        }
    }
}