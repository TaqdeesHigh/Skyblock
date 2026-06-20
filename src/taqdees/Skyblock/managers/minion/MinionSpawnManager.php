<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Air;
use pocketmine\math\Vector3;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;

class MinionSpawnManager {

    private Main $plugin;
    private MinionDataManager $dataManager;

    public function __construct(Main $plugin, MinionDataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function createMinionEgg(string $minionType, int $level = 1): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§6" . ucfirst($minionType) . " Minion §7(Level " . $level . ")");
        $egg->setLore([
            "§7Type: §e" . ucfirst($minionType),
            "§7Level: §a" . $level,
            "",
            "§7Place on a solid block to deploy!",
            "",
            "§eRight-click to place!"
        ]);
        $nbt = $egg->getNamedTag();
        $nbt->setString("minion_type", $minionType);
        $nbt->setInt("minion_level", $level);
        $nbt->setString("minion_egg", "true");
        return $egg;
    }

    public function spawnMinion(Player $player, Position $position, string $minionType, int $level = 1): bool {
        return $this->spawnMinionInternal($player, $position, $minionType, $level);
    }

    public function spawnMinionFromEgg(Player $player, Position $position, \pocketmine\item\Item $egg): bool {
        $nbt = $egg->getNamedTag();
        if ($nbt->getString("minion_egg", "") !== "true") {
            return false;
        }
        $minionType = $nbt->getString("minion_type", "");
        $minionLevel = $nbt->getInt("minion_level", 1);
        if (empty($minionType)) {
            return false;
        }
        return $this->spawnMinionInternal($player, $position, $minionType, $minionLevel);
    }

    private function spawnMinionInternal(Player $player, Position $position, string $minionType, int $level): bool {
        $currentMinionCount = $this->dataManager->getPlayerMinionCount($player->getName());
        $maxMinions = $this->plugin->getConfigValue("max_minions_per_player", 10);
        if ($currentMinionCount >= $maxMinions) {
            $player->sendMessage("§cYou have reached the maximum number of minions! (" . $maxMinions . ")");
            return false;
        }

        $world = $position->getWorld();
        $underBlock = $world->getBlockAt(
            (int)floor($position->getX()),
            (int)floor($position->getY()) - 1,
            (int)floor($position->getZ())
        );

        if ($underBlock instanceof Air || !$underBlock->isSolid()) {
            $player->sendMessage("§cYou must place the minion on a solid block!");
            return false;
        }

        $airCount = 0;
        $requiredAir = 16;
        $radius = 2;
        $platformY = (int)floor($position->getY()) - 1;

        for ($x = -$radius; $x <= $radius; $x++) {
            for ($z = -$radius; $z <= $radius; $z++) {
                if ($x === 0 && $z === 0) continue;
                $block = $world->getBlockAt(
                    (int)floor($position->getX()) + $x,
                    $platformY,
                    (int)floor($position->getZ()) + $z
                );
                if ($block instanceof Air) {
                    $airCount++;
                }
            }
        }

        if ($airCount < $requiredAir) {
            $player->sendMessage("§cNot enough space! The minion needs at least §e" . $requiredAir . " §cfree slots around it to build its platform. Only §e" . $airCount . " §cfound.");
            return false;
        }

        $location = new \pocketmine\entity\Location(
            $position->getX(),
            $position->getY(),
            $position->getZ(),
            $world,
            0,
            0
        );

        $minion = $this->dataManager->createMinionByType($minionType, $location);
        if ($minion === null) {
            $player->sendMessage("§cInvalid minion type: " . $minionType);
            return false;
        }

        $minion->setLevel($level);
        $minion->spawnToAll();
        $this->dataManager->addPlayerMinion($player->getName(), $minion);
        $this->dataManager->saveMinionData($player->getName(), $position, $minionType, $level);

        $player->sendMessage("§aMinion placed! §7(" . ucfirst($minionType) . " Level " . $level . ")");
        return true;
    }

    public function spawnMinionForIsland(Player $player, Position $position, string $minionType): bool {
        return $this->spawnMinion($player, $position, $minionType, 1);
    }
}