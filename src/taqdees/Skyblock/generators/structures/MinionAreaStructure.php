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
        $minionX = $centerX + 5;
        $minionZ = $centerZ;
        $minionY = $centerY + 2;
        for ($dx = -2; $dx <= 2; $dx++) {
            for ($dz = -2; $dz <= 2; $dz++) {
                $world->setBlockAt($minionX + $dx, $minionY, $minionZ + $dz, VanillaBlocks::COBBLESTONE());
            }
        }
        $corners = [[-2, -2], [2, -2], [-2, 2], [2, 2]];
        foreach ($corners as $corner) {
            $world->setBlockAt($minionX + $corner[0], $minionY, $minionZ + $corner[1], VanillaBlocks::STONE_BRICKS());
        }
        if ($player !== null) {
            $this->spawnMinionInCenter($world, $minionX, $minionY + 1, $minionZ, $player, $minionType);
        }
    }

    private function spawnMinionInCenter(World $world, int $x, int $y, int $z, Player $player, string $minionType): void {
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