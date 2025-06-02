<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\player\GameMode;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\services\PlayerCleanupService;
use taqdees\Skyblock\services\PlayerResetService;

class PlayerStateManager {

    private Main $plugin;
    private PlayerCleanupService $cleanupService;
    private PlayerResetService $resetService;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->cleanupService = new PlayerCleanupService($plugin);
        $this->resetService = new PlayerResetService($plugin);
    }

    public function setupPlayerForIsland(Player $player): void {
        $player->setGamemode(GameMode::SURVIVAL());
        $this->cleanupService->cleanupPlayerUI($player);
        $this->resetService->resetPlayerStats($player);
        if ($this->plugin->getConfigValue('island.clear_inventory_on_create', false)) {
            $this->resetService->clearPlayerInventory($player);
        }
    }
}