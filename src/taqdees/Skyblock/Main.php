<?php

declare(strict_types=1);

namespace taqdees\Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\WorldManager;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use muqsit\invmenu\InvMenuHandler;
use taqdees\Skyblock\commands\IslandCommand;
use taqdees\Skyblock\commands\AdminCommand;
use taqdees\Skyblock\listeners\EventListener;
use taqdees\Skyblock\managers\IslandManager;
use taqdees\Skyblock\managers\CraftingManager;
use taqdees\Skyblock\managers\DataManager;
use taqdees\Skyblock\managers\NPCManager;
use taqdees\Skyblock\entities\OzzyNPC;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\managers\MinionManager;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;
use taqdees\Skyblock\minions\MinionCropHandler;
use taqdees\Skyblock\entities\MinionTypes\mining\CobblestoneMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\CoalMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\IronMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\GoldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\DiamondMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\EmeraldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\LapisMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\RedstoneMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\WheatMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\CarrotMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PotatoMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\MelonMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PumpkinMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\OakMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\SpruceMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\BirchMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\AcaciaMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\DarkOakMinion;

class Main extends PluginBase {

    private IslandManager $islandManager;
    private DataManager $dataManager;
    private NPCManager $npcManager;
    private AdminCommand $adminCommand;
    private IslandCommand $islandCommand;
    private MinionManager $minionManager;
    private CraftingManager $craftingManager;
    private MinionCropHandler $minionCropHandler;
    private array $adminEditMode = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("skins/ozzy.png");
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        
        ProfessionRegistry::init();
        
        $this->registerEntities();
        $this->registerGenerators();
        $this->initializeManagers();
        $this->registerListeners();
        $this->getLogger()->info("§eRegistered " . $this->getRegisteredMinionCount() . " minion types");
    }

    private function registerEntities(): void {
        \taqdees\Skyblock\entities\OzzyNPC::registerEntity($this);
        \taqdees\Skyblock\minions\MinionRegistry::init($this);
    }


    private function registerGenerators(): void {
        \taqdees\Skyblock\generators\VoidWorldGenerator::register();
    }
    
    private function initializeManagers(): void {
        $this->dataManager = new DataManager($this);
        $this->islandManager = new IslandManager($this, $this->dataManager);
        $this->npcManager = new NPCManager($this);
        $this->adminCommand = new AdminCommand($this);
        $this->islandCommand = new IslandCommand($this, $this->islandManager);
        $this->minionCropHandler = new MinionCropHandler($this);
        $this->minionManager = new MinionManager($this);
        $this->craftingManager = new CraftingManager($this); 
    }

    private function registerListeners(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
    
    private function getRegisteredMinionCount(): int {
        return 18;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can only be used in-game!");
            return true;
        }

        switch ($command->getName()) {
            case "skyblock":
                return $this->adminCommand->handleCommand($sender, $args);
            case "island":
                return $this->islandCommand->handleCommand($sender, $args);
        }
        return false;
    }

    public function getIslandManager(): IslandManager {
        return $this->islandManager;
    }

    public function getDataManager(): DataManager {
        return $this->dataManager;
    }

    public function getNPCManager(): NPCManager {
        return $this->npcManager;
    }

    public function isInEditMode(string $playerName): bool {
        return isset($this->adminEditMode[$playerName]);
    }

    public function setEditMode(string $playerName, bool $enabled): void {
        if ($enabled) {
            $this->adminEditMode[$playerName] = true;
        } else {
            unset($this->adminEditMode[$playerName]);
        }
    }

    public function getAdminEditMode(): array {
        return $this->adminEditMode;
    }

    public function getMinionManager(): MinionManager {
        return $this->minionManager;
    }

    public function getCraftingManager(): CraftingManager {
        return $this->craftingManager;
    }

    public function getMinionCropHandler(): MinionCropHandler {
        return $this->minionCropHandler;
    }

    public function getConfigValue(string $path, $default = null) {
        $keys = explode('.', $path);
        $value = $this->getConfig()->getAll();
        
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        
        return $value;
    }

    public function onDisable(): void {
        $this->getLogger()->info("§eSaving all minion data...");
        
        $minionCount = 0;
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof BaseMinion) {
                    $entity->onServerShutdown();
                    $minionCount++;
                }
            }
        }
        
        $this->getLogger()->info("§aSaved " . $minionCount . " minions successfully!");
        $this->getLogger()->info("§aSkyblock plugin disabled!");
    }
}