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
use taqdees\Skyblock\managers\DataManager;
use taqdees\Skyblock\managers\NPCManager;
use taqdees\Skyblock\entities\OzzyNPC;

class Main extends PluginBase {

    private IslandManager $islandManager;
    private DataManager $dataManager;
    private NPCManager $npcManager;
    private AdminCommand $adminCommand;
    private IslandCommand $islandCommand;
    
    /** @var array<string, bool> */
    private array $adminEditMode = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        
        $this->registerEntities();
        $this->registerGenerators();
        $this->initializeManagers();
        $this->registerListeners();
    }

    private function registerEntities(): void {
        EntityFactory::getInstance()->register(OzzyNPC::class, function(World $world, CompoundTag $nbt): OzzyNPC {
            return new OzzyNPC($this, EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['OzzyNPC', 'taqdees:ozzynpc']);
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
    }

    private function registerListeners(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
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
    
}