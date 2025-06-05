<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities;

use pocketmine\entity\Living;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use taqdees\Skyblock\managers\MinionManager;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\minions\professions\Profession;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;
use pocketmine\block\VanillaBlocks;

abstract class BaseMinion extends Human {

    protected string $minionType;
    protected string $customName;
    protected Main $plugin;
    protected int $level = 1;
    public int $maxLevel = 11;
    protected float $workRadius = 2.0;
    protected int $workCooldown = 20;
    protected int $lastWorkTick = 0;
    protected bool $isWorking = false;
    protected ?Vector3 $lockedPosition = null;
    protected bool $positionLocked = false;
    protected ?Vector3 $targetBlock = null;
    protected int $breakingTick = 0;
    protected int $breakTime = 30;
    protected int $breakCooldown = 100;
    protected int $lastBreakTick = 0;
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
        return $professionName . " " . $this->customName . " §7(Lv. " . $this->level . ")";
    }

    public function getProfession(): ?Profession {
        return $this->profession;
    }

    public function getCurrentTool(): ?Item {
        if ($this->profession === null) {
            return null;
        }
        
        $tools = $this->profession->getTools();
        $toolIndex = min($this->level - 1, count($tools) - 1);
        return $tools[$toolIndex] ?? null;
    }

    public function getMaxLevel(): int {
        return $this->maxLevel;
    }

    public function getMinionType(): string {
        return $this->minionType;
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function setLevel(int $level): void {
        $this->level = max(1, min($level, $this->maxLevel));
        $this->updateWorkStats();
        $this->updateEquipment();
        $this->setNameTag($this->getDisplayName());
    }

    protected function updateWorkStats(): void {
        $this->workCooldown = max(5, 20 - ($this->level - 1) * 2);
        $this->breakTime = max(10, 30 - ($this->level - 1) * 2);
        $this->breakCooldown = max(20, 100 - ($this->level - 1) * 8);
    }

    protected function updateEquipment(): void {
        $tool = $this->getCurrentTool();
        if ($tool !== null) {
            $this->getInventory()->setItemInHand($tool);
        }
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->setNameTag($this->getDisplayName());
        $this->setNameTagAlwaysVisible(true);
        $this->setCanSaveWithChunk(true);
        $this->lockPosition();
        $this->setHasGravity(false);
        $this->setMotion(new Vector3(0, 0, 0));
        $this->updateWorkStats();
        $this->updateEquipment();
        $this->loadCustomSkin();
        $this->setScale(0.6);
    }

    private function lockPosition(): void {
        $this->lockedPosition = new Vector3($this->location->x, $this->location->y, $this->location->z);
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

    protected function loadCustomSkin(): void {
        $skinPath = $this->plugin->getDataFolder() . "resources/skins/" . $this->minionType . ".png";
        
        if (file_exists($skinPath)) {
            try {
                $skinData = $this->convertPngToSkinData($skinPath);
                if ($skinData !== null) {
                    $skin = new Skin($this->minionType . "_minion", $skinData);
                    $this->setSkin($skin);
                    return;
                }
            } catch (\Exception $e) {
                $this->plugin->getLogger()->warning("Failed to load minion skin: " . $e->getMessage());
            }
        }
        $this->setSkin($this->getDefaultSkin());
    }

    private function convertPngToSkinData(string $pngPath): ?string {
        if (!extension_loaded('gd')) {
            $this->plugin->getLogger()->warning("GD extension not loaded, cannot process PNG skins");
            return null;
        }

        $image = imagecreatefrompng($pngPath);
        if (!$image) {
            return null;
        }
        if (imagesx($image) !== 64 || imagesy($image) !== 64) {
            imagedestroy($image);
            return null;
        }

        $skinData = "";
        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $a = (imagecolorsforindex($image, $color)['alpha'] ?? 0) === 127 ? 0 : 255;
                
                $skinData .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        imagedestroy($image);
        return $skinData;
    }

    protected function getDefaultSkin(): Skin {
        $color = $this->getMinionColor();
        $skinData = str_repeat($color, 64 * 64);
        
        return new Skin("default_" . $this->minionType, $skinData);
    }

    protected function getMinionColor(): string {
        if ($this->profession !== null) {
            switch ($this->profession->getColor()) {
                case "§a":
                    return "\x00\xFF\x00\xFF";
                case "§7":
                    return "\x7F\x7F\x7F\xFF";
                case "§6":
                    return "\xFF\xD7\x00\xFF";
                case "§b":
                    return "\x00\xFF\xFF\xFF";
                default:
                    return "\x8B\x69\x3D\xFF";
            }
        }
        return "\x8B\x69\x3D\xFF";
    }

    public function onUpdate(int $currentTick): bool {
        $this->enforcePosition();
        $this->setMotion(new Vector3(0, 0, 0));
        
        if ($this->targetBlock !== null) {
            $this->breakingTick++;
            $this->lookAtBlock($this->targetBlock);
            
            if ($this->breakingTick >= $this->breakTime) {
                $this->finishBreaking();
                $this->breakingTick = 0;
                $this->targetBlock = null;
                $this->lastBreakTick = $currentTick;
            }
        } else {
            if ($currentTick - $this->lastBreakTick >= $this->breakCooldown) {
                if ($currentTick - $this->lastWorkTick >= $this->workCooldown) {
                    $this->findWork();
                    $this->lastWorkTick = $currentTick;
                }
            }
        }
        
        $result = parent::onUpdate($currentTick);
        $this->enforcePosition();
        
        return $result;
    }

    protected function findWork(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $workPositions = [];
        $platformY = floor($pos->y - 1);
        
        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                if ($x == 0 && $z == 0) {
                    continue;
                }
                
                $blockPos = new Vector3(
                    floor($pos->x) + $x, 
                    $platformY, 
                    floor($pos->z) + $z
                );
                
                if ($this->canWorkOnBlock($blockPos)) {
                    $workPositions[] = $blockPos;
                }
            }
        }
        if (!empty($workPositions)) {
            $randomIndex = array_rand($workPositions);
            $this->targetBlock = $workPositions[$randomIndex];
            $this->breakingTick = 0;
            $this->rotateTowardsBlock($this->targetBlock);
            return;
        }
        $this->generatePlatform();
    }

    protected function generatePlatform(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $platformY = floor($pos->y - 1);
        
        for ($x = -$this->workRadius; $x <= $this->workRadius; $x++) {
            for ($z = -$this->workRadius; $z <= $this->workRadius; $z++) {
                $blockPos = new Vector3(
                    floor($pos->x) + $x, 
                    $platformY, 
                    floor($pos->z) + $z
                );
                
                $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);
                if ($block->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
                    $world->setBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z, VanillaBlocks::COBBLESTONE());
                }
            }
        }
    }

    protected function canWorkOnBlock(Vector3 $blockPos): bool {
        return false;
    }

    protected function finishBreaking(): void {
        if ($this->targetBlock === null) return;
        
        $world = $this->getWorld();
        $blockPos = $this->targetBlock;
        $this->doWork();
    }

    protected function rotateTowardsBlock(Vector3 $blockPos): void {
        $pos = $this->getPosition();
        $dx = $blockPos->x - $pos->x;
        $dz = $blockPos->z - $pos->z;
        $yaw = atan2(-$dx, $dz) * 180 / M_PI;
        if ($yaw < 0) {
            $yaw += 360;
        }
        
        $this->location->yaw = $yaw;
        $this->setRotation($yaw, $this->location->pitch);
    }

    protected function lookAtBlock(Vector3 $blockPos): void {
        $pos = $this->getPosition();
        $dx = $blockPos->x - $pos->x;
        $dy = $blockPos->y - $pos->y;
        $dz = $blockPos->z - $pos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);
        $pitch = -atan2($dy, $distance) * 180 / M_PI;
        $yaw = atan2(-$dx, $dz) * 180 / M_PI;
        if ($yaw < 0) {
            $yaw += 360;
        }
        
        $this->location->yaw = $yaw;
        $this->location->pitch = $pitch;
        $this->setRotation($yaw, $pitch);
    }

    protected function doWork(): void {}

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
        $this->plugin->getMinionManager()->openMinionInventoryMenu($player, $this);
        return true;
    }

    public function getSaveId(): string {
        return $this->minionType . "_minion";
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
        
        $this->setNameTag($this->getDisplayName());
        $this->updateWorkStats();
        $this->updateEquipment();
        $this->setScale(0.6);
    }
}