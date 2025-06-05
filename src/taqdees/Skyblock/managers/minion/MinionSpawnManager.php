<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;

class MinionSpawnManager {

    private Main $plugin;
    private MinionDataManager $dataManager;
    private const MAX_MINIONS_PER_PLAYER = 5;

    public function __construct(Main $plugin, MinionDataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function createMinionEgg(string $minionType): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§6" . ucfirst($minionType) . " Minion Egg");
        $egg->setLore([
            "§7Right-click to spawn a " . $minionType . " minion",
            "§7Automatically works for you!"
        ]);
        $egg->getNamedTag()->setString("minionType", $minionType);
        return $egg;
    }

    public function spawnMinion(Player $player, Position $position, string $minionType): bool {
        if ($this->dataManager->getPlayerMinionCount($player->getName()) >= self::MAX_MINIONS_PER_PLAYER) {
            $player->sendMessage("§cYou have reached the maximum number of minions (" . self::MAX_MINIONS_PER_PLAYER . ")!");
            return false;
        }

        $location = new \pocketmine\entity\Location(
            $position->getX(),
            $position->getY(),
            $position->getZ(),
            $position->getWorld(),
            0,
            0
        );

        $minion = $this->dataManager->createMinionByType($minionType, $location);
        if ($minion === null) {
            $player->sendMessage("§cInvalid minion type: " . $minionType);
            return false;
        }

        $minion->spawnToAll();
        $minion->loadFromFile();
        
        $this->dataManager->addPlayerMinion($player->getName(), $minion);
        $this->dataManager->saveMinionData($player->getName(), $position, $minionType, 1);
        
        $player->sendMessage("§a" . ucfirst($minionType) . " minion has been spawned!");
        return true;
    }

    public function spawnMinionForIsland(Player $player, Position $position, string $minionType): bool {
        $location = new \pocketmine\entity\Location(
            $position->getX(),
            $position->getY(),
            $position->getZ(),
            $position->getWorld(),
            0,
            0
        );

        $minion = $this->dataManager->createMinionByType($minionType, $location);
        if ($minion === null) {
            $this->plugin->getLogger()->warning("Failed to create minion type: " . $minionType);
            return false;
        }

        $minion->spawnToAll();
        $minion->loadFromFile();
        
        $this->dataManager->addPlayerMinion($player->getName(), $minion);
        $this->dataManager->saveMinionData($player->getName(), $position, $minionType, 1);
        
        $player->sendMessage("§aA " . ucfirst($minionType) . " minion has been spawned on your island!");
        return true;
    }
}