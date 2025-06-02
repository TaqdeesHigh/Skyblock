<?php

declare(strict_types=1);

namespace taqdees\Skyblock\services;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use taqdees\Skyblock\Main;

class PlayerCleanupService {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function cleanupPlayerUI(Player $player): void {
        $this->removeScoreboard($player);
        $this->removeBossBars($player);
        $this->clearActionBar($player);
    }

    public function removeScoreboard(Player $player): void {
        try {
            $removePacket = new RemoveObjectivePacket();
            $removePacket->objectiveName = "sidebar";
            $player->getNetworkSession()->sendDataPacket($removePacket);
            $commonNames = ["scoreboard", "stats", "info", "display", "main"];
            foreach ($commonNames as $name) {
                $removePacket = new RemoveObjectivePacket();
                $removePacket->objectiveName = $name;
                $player->getNetworkSession()->sendDataPacket($removePacket);
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->debug("Could not remove scoreboard for " . $player->getName() . ": " . $e->getMessage());
        }
    }

    public function removeBossBars(Player $player): void {
        try {
            for ($i = 0; $i < 10; $i++) {
                $bossPacket = new BossEventPacket();
                $bossPacket->bossActorUniqueId = $i;
                $bossPacket->eventType = 2;
                $player->getNetworkSession()->sendDataPacket($bossPacket);
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->debug("Could not remove boss bars for " . $player->getName() . ": " . $e->getMessage());
        }
    }

    public function clearActionBar(Player $player): void {
        $player->sendActionBarMessage("");
    }
}