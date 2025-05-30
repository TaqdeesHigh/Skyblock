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
use taqdees\Skyblock\Main;

class OzzyNPC extends Human {

    private string $customName = "Ozzy";
    private Main $plugin;
    private bool $isGrounded = false;
    private float $groundY = 0.0;
    private bool $positionLocked = false;
    private ?Vector3 $lockedPosition = null;

    public function __construct(Main $plugin, Location $location = null, Skin $skin = null, CompoundTag $nbt = null) {
        $this->plugin = $plugin;
        if ($location === null) {
            $world = $plugin->getServer()->getWorldManager()->getDefaultWorld();
            if ($world === null) {
                throw new \RuntimeException("Default world not found");
            }
            $location = new Location(0, 64, 0, $world, 0, 0);
        }
        if ($skin === null) {
            $skinData = str_repeat("\x8B\x69\x3D\xFF", 64 * 64);
            $skin = new Skin("default_ozzy", $skinData);
        }
        parent::__construct($location, $skin, $nbt);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.6);
    }

    public function getName(): string {
        return "OzzyNPC";
    }

    public function getDisplayName(): string {
        return $this->customName;
    }

    public function setDisplayName(string $name): void {
        $this->customName = $name;
        $this->setNameTag($name);
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        
        $this->setNameTag($this->customName);
        $this->setNameTagAlwaysVisible(true);
        $this->setCanSaveWithChunk(true);
        $this->loadCustomSkin();
        $this->findGroundLevel();
        $this->lockPosition();
        $this->setHasGravity(false);
        $this->setMotion(new Vector3(0, 0, 0));
    }

    private function findGroundLevel(): void {
        $world = $this->getWorld();
        $x = (int) floor($this->location->x);
        $z = (int) floor($this->location->z);
        $startY = (int) floor($this->location->y);
        for ($y = $startY; $y >= $world->getMinY(); $y--) {
            $block = $world->getBlockAt($x, $y, $z);
            if ($block->isSolid() && !$block->isTransparent()) {
                $this->groundY = $y + 1.0;
                $this->isGrounded = true;
                $this->location->y = $this->groundY;
                break;
            }
        }
        if (!$this->isGrounded) {
            $this->groundY = $this->location->y;
            $this->isGrounded = true;
        }
    }

    private function lockPosition(): void {
        $this->lockedPosition = new Vector3($this->location->x, $this->groundY, $this->location->z);
        $this->positionLocked = true;
    }

    private function enforcePosition(): void {
        if ($this->positionLocked && $this->lockedPosition !== null) {
            $currentPos = $this->getPosition();
            if ($currentPos->distance($this->lockedPosition) > 0.01) {
                $this->location->x = $this->lockedPosition->x;
                $this->location->y = $this->lockedPosition->y;
                $this->location->z = $this->lockedPosition->z;
                parent::setPosition($this->lockedPosition);
            }
        }
    }

    private function loadCustomSkin(): void {
        $plugin = $this->plugin;
        $skinPath = $plugin->getDataFolder() . "skins/ozzy.png";
        
        if (file_exists($skinPath)) {
            try {
                $skinData = file_get_contents($skinPath);
                if ($skinData !== false) {
                    $skin = new Skin("ozzy_npc", $skinData);
                    $this->setSkin($skin);
                    return;
                }
            } catch (\Exception $e) {
                $plugin->getLogger()->warning("Failed to load custom skin: " . $e->getMessage());
            }
        }
        $this->setDefaultSkin();
    }

    private function setDefaultSkin(): void {
        $skinData = str_repeat("\x8B\x69\x3D\xFF", 64 * 64);
        $skin = new Skin("default_ozzy", $skinData);
        $this->setSkin($skin);
    }

    public function onUpdate(int $currentTick): bool {
        $this->enforcePosition();
        $this->setMotion(new Vector3(0, 0, 0));
        $result = parent::onUpdate($currentTick);
        $this->enforcePosition();
        
        return $result;
    }

    protected function applyGravity(): void {}

    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->setMotion(new Vector3(0, 0, 0));
        $this->enforcePosition();
        $result = parent::entityBaseTick($tickDiff);
        $this->enforcePosition();
        
        return $result;
    }

    protected function checkBlockCollision(): void {}

    public function move(float $dx, float $dy, float $dz): void {
        if ($this->positionLocked) {
            return;
        }
        parent::move($dx, $dy, $dz);
    }

    public function teleport(Vector3 $pos, ?float $yaw = null, ?float $pitch = null): bool {
        $wasLocked = $this->positionLocked;
        $this->positionLocked = false;
        $result = parent::teleport($pos, $yaw, $pitch);
        if ($result) {
            $this->findGroundLevel();
            $this->lockPosition();
        } else {
            $this->positionLocked = $wasLocked;
        }
        return $result;
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

    public function onInteract(Player $player, Vector3 $clickVector): bool {
        $this->plugin->getNPCManager()->openNPCMenu($player, $this);
        return true;
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("customName", $this->customName);
        $nbt->setFloat("groundY", $this->groundY);
        $nbt->setByte("isGrounded", $this->isGrounded ? 1 : 0);
        if ($this->lockedPosition !== null) {
            $nbt->setFloat("lockedX", $this->lockedPosition->x);
            $nbt->setFloat("lockedY", $this->lockedPosition->y);
            $nbt->setFloat("lockedZ", $this->lockedPosition->z);
        }
        return $nbt;
    }

    public function readSaveData(CompoundTag $nbt): void {
        parent::readSaveData($nbt);
        $this->customName = $nbt->getString("customName", "Ozzy");
        $this->groundY = $nbt->getFloat("groundY", $this->location->y);
        $this->isGrounded = $nbt->getByte("isGrounded", 1) === 1;
        
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
        
        $this->setNameTag($this->customName);
    }
}