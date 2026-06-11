<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\minion;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Air;
use pocketmine\block\Farmland;
use pocketmine\math\Vector3;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\BaseMinion;

class MinionSpawnManager {

    private Main $plugin;
    private MinionDataManager $dataManager;

    private const SURFACE_REQUIREMENTS = [
        'cobblestone' => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'coal'        => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'iron'        => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'gold'        => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'diamond'     => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'lapis'       => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'emerald'     => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'redstone'    => ['surface' => 'solid',    'work_level' => 'platform', 'needs_air' => false, 'description' => 'a solid surface'],
        'wheat'       => ['surface' => 'farmland', 'work_level' => 'platform', 'needs_air' => true,  'description' => 'farmland with open space above'],
        'carrot'      => ['surface' => 'farmland', 'work_level' => 'platform', 'needs_air' => true,  'description' => 'farmland with open space above'],
        'potato'      => ['surface' => 'farmland', 'work_level' => 'platform', 'needs_air' => true,  'description' => 'farmland with open space above'],
        'pumpkin'     => ['surface' => 'dirt',     'work_level' => 'platform', 'needs_air' => true,  'description' => 'dirt or grass with open space above'],
        'melon'       => ['surface' => 'dirt',     'work_level' => 'platform', 'needs_air' => true,  'description' => 'dirt or grass with open space above'],
        'oak'         => ['surface' => 'dirt',     'work_level' => 'surface',  'needs_air' => true,  'description' => 'dirt or grass with open space around for logs'],
        'spruce'      => ['surface' => 'dirt',     'work_level' => 'surface',  'needs_air' => true,  'description' => 'dirt or grass with open space around for logs'],
        'birch'       => ['surface' => 'dirt',     'work_level' => 'surface',  'needs_air' => true,  'description' => 'dirt or grass with open space around for logs'],
        'acacia'      => ['surface' => 'dirt',     'work_level' => 'surface',  'needs_air' => true,  'description' => 'dirt or grass with open space around for logs'],
        'dark_oak'    => ['surface' => 'dirt',     'work_level' => 'surface',  'needs_air' => true,  'description' => 'dirt or grass with open space around for logs'],
    ];

    private const MIN_OPEN_SLOTS = 5;
    private const WORK_RADIUS = 2;

    public function __construct(Main $plugin, MinionDataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function createMinionEgg(string $minionType, int $level = 1): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§6" . ucfirst($minionType) . " Minion §7(Level " . $level . ")");

        $req = self::SURFACE_REQUIREMENTS[$minionType] ?? null;
        $reqLine = $req !== null ? "§7Requires: §e" . $req['description'] : "§7Requires: §eany surface";

        $egg->setLore([
            "§7Type: §e" . ucfirst($minionType),
            "§7Level: §a" . $level,
            "",
            $reqLine,
            "§7Needs §e" . self::MIN_OPEN_SLOTS . "+ open slots §7in work area",
            "",
            "§7Place this minion on your island",
            "§7to start automatic resource collection!",
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
        if (!$this->canPlaceMinionAt($player, $position)) {
            $player->sendMessage("§cYou can only place minions on your island!");
            return false;
        }

        $currentMinionCount = $this->dataManager->getPlayerMinionCount($player->getName());
        $maxMinions = $this->plugin->getConfigValue("max_minions_per_player", 10);
        if ($currentMinionCount >= $maxMinions) {
            $player->sendMessage("§cYou have reached the maximum number of minions! (" . $maxMinions . ")");
            return false;
        }

        $surfaceError = $this->validateSurface($position, $minionType);
        if ($surfaceError !== null) {
            $player->sendMessage("§c" . $surfaceError);
            return false;
        }

        $openSlots = $this->countOpenWorkSlots($position, $minionType);
        if ($openSlots < self::MIN_OPEN_SLOTS) {
            $req = self::SURFACE_REQUIREMENTS[$minionType] ?? null;
            $desc = $req['description'] ?? 'compatible blocks';
            $player->sendMessage("§cNot enough space! This minion needs at least §e" . self::MIN_OPEN_SLOTS . " §copen slots of §e" . $desc . " §caround it. Only §e" . $openSlots . " §cfound.");
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

        $minion->setLevel($level);
        $minion->spawnToAll();
        $this->dataManager->addPlayerMinion($player->getName(), $minion);
        $this->dataManager->saveMinionData($player->getName(), $position, $minionType, $level);

        $player->sendMessage("§aMinion placed successfully! §7(" . ucfirst($minionType) . " Level " . $level . ")");
        return true;
    }

    private function validateSurface(Position $position, string $minionType): ?string {
        $req = self::SURFACE_REQUIREMENTS[$minionType] ?? null;
        if ($req === null) return null;

        $world = $position->getWorld();
        $underPos = new Vector3($position->getX(), $position->getY() - 1, $position->getZ());
        $underBlock = $world->getBlockAt((int)$underPos->x, (int)$underPos->y, (int)$underPos->z);

        $valid = match($req['surface']) {
            'farmland' => $underBlock instanceof Farmland,
            'dirt'     => in_array($underBlock->getTypeId(), [
                              VanillaBlocks::DIRT()->getTypeId(),
                              VanillaBlocks::GRASS()->getTypeId(),
                              VanillaBlocks::PODZOL()->getTypeId(),
                          ], true),
            'solid'    => !($underBlock instanceof Air) && $underBlock->isSolid(),
            default    => true,
        };

        if (!$valid) {
            $names = ['farmland' => 'farmland', 'dirt' => 'dirt or grass', 'solid' => 'a solid block'];
            return "This minion must be placed on " . ($names[$req['surface']] ?? 'a compatible surface') . "!";
        }

        return null;
    }

    private function countOpenWorkSlots(Position $position, string $minionType): int {
        $req = self::SURFACE_REQUIREMENTS[$minionType] ?? null;
        $world = $position->getWorld();

        // foraging checks Y (same level as minion, where logs will sit on dirt)
        // everything else checks Y-1 (the platform level under the minion)
        $checkY = ($req !== null && $req['work_level'] === 'surface')
            ? (int)floor($position->getY())
            : (int)floor($position->getY() - 1);

        $needsAir = $req['needs_air'] ?? false;
        $open = 0;

        for ($x = -self::WORK_RADIUS; $x <= self::WORK_RADIUS; $x++) {
            for ($z = -self::WORK_RADIUS; $z <= self::WORK_RADIUS; $z++) {
                if ($x === 0 && $z === 0) continue;

                $blockPos = new Vector3(
                    floor($position->getX()) + $x,
                    $checkY,
                    floor($position->getZ()) + $z
                );
                $block = $world->getBlockAt((int)$blockPos->x, (int)$blockPos->y, (int)$blockPos->z);

                if ($needsAir) {
                    if ($block instanceof Air) {
                        $open++;
                    }
                } else {
                    if ($block instanceof Air) {
                        $open++;
                    } elseif ($req !== null) {
                        $compatible = match($req['surface']) {
                            'farmland' => $block instanceof Farmland,
                            'dirt'     => in_array($block->getTypeId(), [
                                              VanillaBlocks::DIRT()->getTypeId(),
                                              VanillaBlocks::GRASS()->getTypeId(),
                                              VanillaBlocks::PODZOL()->getTypeId(),
                                          ], true),
                            'solid'    => $block->isSolid(),
                            default    => false,
                        };
                        if ($compatible) $open++;
                    }
                }
            }
        }

        return $open;
    }

    public function spawnMinionForIsland(Player $player, Position $position, string $minionType): bool {
        return $this->spawnMinion($player, $position, $minionType, 1);
    }

    private function canPlaceMinionAt(Player $player, Position $position): bool {
        return true;
    }
}