<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\math\Vector3;
use pocketmine\entity\Entity;

trait MinionMovementTrait {
    protected ?Vector3 $lockedPosition = null;
    protected bool $positionLocked = false;

    protected function initializeMovement(): void {
        $this->lockPosition();
        $this->setHasGravity(false);
        $this->setMotion(new Vector3(0, 0, 0));
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

    protected function handleMovement(): void {
        $this->enforcePosition();
        $this->setMotion(new Vector3(0, 0, 0));
    }

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

    protected function applyGravity(): void {}

    protected function checkBlockCollision(): void {}

    public function entityBaseTick(int $tickDiff = 1): bool {
        $this->setMotion(new Vector3(0, 0, 0));
        $this->enforcePosition();
        $result = parent::entityBaseTick($tickDiff);
        $this->enforcePosition();
        
        return $result;
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
}