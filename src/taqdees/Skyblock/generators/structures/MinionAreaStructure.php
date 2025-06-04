<?php

declare(strict_types=1);

namespace taqdees\Skyblock\generators\structures;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use taqdees\Skyblock\Main;

class MinionAreaStructure {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function generate(World $world, Position $center, Player $player = null, string $minionType = "cobblestone"): void {
        $centerX = (int)$center->getX();
        $centerY = (int)$center->getY();
        $centerZ = (int)$center->getZ();
        $platformCenterX = $centerX + 3;
        $platformCenterZ = $centerZ + 3;
        $platformY = $centerY + 3;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($platformCenterX + $dx, $platformY, $platformCenterZ + $dz, VanillaBlocks::COBBLESTONE());
            }
        }
        if ($player !== null) {
            $this->spawnMinionInCenter(
                $world, 
                $platformCenterX + 0.5,
                $platformY + 1, 
                $platformCenterZ + 0.5,
                $player, 
                $minionType
            );
        }
    }

    private function spawnMinionInCenter(World $world, float $x, int $y, float $z, Player $player, string $minionType): void {
        $minionPosition = new Position($x, $y, $z, $world);
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new class($this->plugin, $player, $minionPosition, $minionType) extends \pocketmine\scheduler\Task {
                private Main $plugin;
                private Player $player;
                private Position $position;
                private string $minionType;

                public function __construct(Main $plugin, Player $player, Position $position, string $minionType) {
                    $this->plugin = $plugin;
                    $this->player = $player;
                    $this->position = $position;
                    $this->minionType = $minionType;
                }

                public function onRun(): void {
                    if ($this->player->isOnline()) {
                        $this->plugin->getMinionManager()->spawnMinionForIsland($this->player, $this->position, $this->minionType);
                    }
                }
            },
            20
        );
    }
}