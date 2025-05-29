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
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\entity\Entity;
use taqdees\Skyblock\Main;

class OzzyNPC extends Human {

    private string $customName = "Ozzy";
    private Main $plugin;
    private int $lookAtCooldown = 0;

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
        $this->setHasGravity(false);
        $this->setMotion(new Vector3(0, 0, 0));
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
        $this->setMotion(new Vector3(0, 0, 0));
        
        if ($this->lookAtCooldown > 0) {
            $this->lookAtCooldown--;
        }
        
        if ($this->lookAtCooldown <= 0) {
            $this->lookAtNearbyPlayers();
            $this->lookAtCooldown = 10;
        }
        
        return parent::onUpdate($currentTick);
    }

    protected function applyGravity(): void {
    }

    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->setMotion(new Vector3(0, 0, 0));
        return parent::entityBaseTick($tickDiff);
    }

    private function lookAtNearbyPlayers(): void {
        $nearbyPlayers = [];
        $maxDistance = 10.0;
        
        foreach ($this->getWorld()->getPlayers() as $player) {
            if ($this->getPosition()->distance($player->getPosition()) <= $maxDistance) {
                $nearbyPlayers[] = $player;
            }
        }
        
        if (!empty($nearbyPlayers)) {
            $closestPlayer = null;
            $closestDistance = $maxDistance + 1;
            
            foreach ($nearbyPlayers as $player) {
                $distance = $this->getPosition()->distance($player->getPosition());
                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closestPlayer = $player;
                }
            }
            
            if ($closestPlayer !== null) {
                $this->lookAtPosition($closestPlayer->getPosition());
            }
        }
    }

    private function lookAtPosition(Vector3 $target): void {
        $horizontal = sqrt(($target->x - $this->location->x) ** 2 + ($target->z - $this->location->z) ** 2);
        $vertical = $target->y - $this->location->y;

        $pitch = -atan2($vertical, $horizontal) / M_PI * 180;
        $yaw = atan2($target->z - $this->location->z, $target->x - $this->location->x) / M_PI * 180 - 90;

        if ($yaw < 0) {
            $yaw += 360.0;
        }

        $this->setRotation($yaw, $pitch);

        $pk = new MoveActorAbsolutePacket();
        $pk->actorRuntimeId = $this->getId();
        $pk->position = $this->getPosition()->asVector3();
        $pk->yaw = $yaw;
        $pk->pitch = $pitch;
        $pk->headYaw = $yaw;
        $pk->flags = MoveActorAbsolutePacket::FLAG_GROUND | MoveActorAbsolutePacket::FLAG_TELEPORT;

        foreach ($this->getViewers() as $viewer) {
            $viewer->getNetworkSession()->sendDataPacket($pk);
        }

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
        return $nbt;
    }

    public function readSaveData(CompoundTag $nbt): void {
        parent::readSaveData($nbt);
        $this->customName = $nbt->getString("customName", "Ozzy");
        $this->setNameTag($this->customName);
    }
}