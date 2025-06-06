<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities;

use pocketmine\entity\Human;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\minions\professions\Profession;
use taqdees\Skyblock\entities\minion\MinionInventoryTrait;
use taqdees\Skyblock\entities\minion\MinionMovementTrait;
use taqdees\Skyblock\entities\minion\MinionWorkTrait;
use taqdees\Skyblock\entities\minion\MinionSkinTrait;
use taqdees\Skyblock\entities\minion\MinionDataTrait;

abstract class BaseMinion extends Human {
    use MinionInventoryTrait, MinionMovementTrait, MinionWorkTrait, MinionSkinTrait, MinionDataTrait;

    protected string $minionType;
    protected string $customName;
    protected Main $plugin;
    protected int $level = 1;
    public int $maxLevel = 11;
    protected ?Profession $profession = null;

    public function __construct(Main $plugin, Location $location, string $minionType, Skin $skin = null, CompoundTag $nbt = null) {
        $this->plugin = $plugin;
        $this->minionType = $minionType;
        $this->customName = ucfirst($minionType) . " Minion";
        $this->profession = $this->initializeProfession();
        
        if ($skin === null) {
            $skin = $this->getDefaultSkin();
        }
        
        parent::__construct($location, $skin, $nbt);
        $this->loadFromFile();
    }

    abstract protected function initializeProfession(): ?Profession;

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.8, 0.4);
    }

    public function getName(): string {
        return "BaseMinion";
    }

    public static function getNetworkTypeId(): string {
        return EntityIds::PLAYER;
    }

    public function getDisplayName(): string {
        $professionName = $this->profession ? $this->profession->getDisplayName() : "§7Unknown";
        $inventoryStatus = $this->getInventoryItemCount() . "/" . $this->getMaxInventorySlots();
        return $professionName . " " . $this->customName . " §7(Lv. " . $this->level . ") §8[" . $inventoryStatus . "]";
    }
    
    public function getProfession(): ?Profession { return $this->profession; }
    public function getMinionType(): string { return $this->minionType; }
    public function getLevel(): int { return $this->level; }
    public function getMaxLevel(): int { return $this->maxLevel; }

    public function setLevel(int $level): void {
        $this->level = max(1, min($level, $this->maxLevel));
        $this->updateWorkStats();
        $this->updateEquipment();
        $this->setNameTag($this->getDisplayName());
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->setNameTag($this->getDisplayName());
        $this->setNameTagAlwaysVisible(true);
        $this->setCanSaveWithChunk(true);
        $this->initializeMovement();
        $this->updateWorkStats();
        $this->updateEquipment();
        $this->loadCustomSkin();
        $this->setScale(0.6);
    }

    public function onUpdate(int $currentTick): bool {
        $this->handleMovement();
        $this->handleAutoSave($currentTick);
        $this->handleWork($currentTick);
        
        $result = parent::onUpdate($currentTick);
        $this->enforcePosition();
        
        return $result;
    }

    public function onInteract(Player $player, Vector3 $clickVector): bool {
        $this->plugin->getMinionManager()->openMinionInventoryMenu($player, $this);
        return true;
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();
    }

    public function canBeCollidedWith(): bool {
        return false;
    }

    public function canCollideWith(Entity $entity): bool {
        return false;
    }

    public function getSaveId(): string {
        return $this->minionType . "_minion";
    }
    
    public function onDispose(): void {
        if (method_exists($this, 'resetBreaking')) {
            $this->resetBreaking();
        }
        parent::onDispose();
    }
    public function flagForDespawn(): void {
        if (method_exists($this, 'resetBreaking')) {
            $this->resetBreaking();
        }
        parent::flagForDespawn();
    }
}