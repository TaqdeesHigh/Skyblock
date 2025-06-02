<?php
declare(strict_types=1);

namespace taqdees\Skyblock\generators;

use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\player\Player;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\generators\components\MainIslandGenerator;
use taqdees\Skyblock\generators\components\SecondIslandGenerator;
use taqdees\Skyblock\generators\components\TreeGenerator;

class IslandGenerator {
    
    private Main $plugin;
    private MainIslandGenerator $mainIslandGenerator;
    private SecondIslandGenerator $secondIslandGenerator;
    private TreeGenerator $treeGenerator;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->mainIslandGenerator = new MainIslandGenerator();
        $this->secondIslandGenerator = new SecondIslandGenerator($plugin);
        $this->treeGenerator = new TreeGenerator();
    }

    public function generateIsland(World $world, Position $center, Player $player = null): bool {
        try {
            $this->mainIslandGenerator->generate($world, $center);
            $this->treeGenerator->generate($world, $center);
            $this->secondIslandGenerator->generate($world, $center, $player);
            
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to generate island: " . $e->getMessage());
            return false;
        }
    }

    public function getSpawnPosition(Position $center): Position {
        return $this->mainIslandGenerator->getSpawnPosition($center);
    }
}