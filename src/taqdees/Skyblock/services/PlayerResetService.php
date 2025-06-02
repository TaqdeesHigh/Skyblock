<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use taqdees\Skyblock\Main;

class PlayerResetService {

    private Main $plugin;
    private const MAX_SATURATION = 20.0;
    private const MAX_EXHAUSTION = 0.0;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function resetPlayerStats(Player $player): void {
        $this->resetEffects($player);
        $this->resetHealth($player);
        $this->resetHunger($player);
    }

    public function resetEffects(Player $player): void {
        try {
            $player->getEffects()->clear();
        } catch (\Exception $e) {
            $this->plugin->getLogger()->debug("Could not clear effects for " . $player->getName() . ": " . $e->getMessage());
        }
    }

    public function resetHealth(Player $player): void {
        try {
            $player->setHealth($player->getMaxHealth());
        } catch (\Exception $e) {
            $this->plugin->getLogger()->debug("Could not reset health for " . $player->getName() . ": " . $e->getMessage());
        }
    }

    public function resetHunger(Player $player): void {
        try {
            $hungerManager = $player->getHungerManager();
            $hungerManager->setFood($hungerManager->getMaxFood());
            $hungerManager->setSaturation(self::MAX_SATURATION);
            if (method_exists($hungerManager, 'setExhaustion')) {
                $hungerManager->setExhaustion(self::MAX_EXHAUSTION);
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->debug("Could not reset hunger for " . $player->getName() . ": " . $e->getMessage());
        }
    }

    public function clearPlayerInventory(Player $player): void {
        try {
            $player->getInventory()->clearAll();
            $player->getCursorInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            if (method_exists($player, 'getOffHandInventory')) {
                $player->getOffHandInventory()->clearAll();
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->debug("Could not clear inventory for " . $player->getName() . ": " . $e->getMessage());
        }
    }
}