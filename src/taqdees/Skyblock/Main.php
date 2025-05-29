<?php

declare(strict_types=1);

namespace taqdees\Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\WorldManager;
use taqdees\Skyblock\commands\IslandCommand;
use taqdees\Skyblock\commands\AdminCommand;
use taqdees\Skyblock\listeners\EventListener;
use taqdees\Skyblock\managers\IslandManager;
use taqdees\Skyblock\managers\DataManager;

class Main extends PluginBase {

    private IslandManager $islandManager;
    private DataManager $dataManager;
    private AdminCommand $adminCommand;
    private IslandCommand $islandCommand;
    
    /** @var array<string, bool> */
    private array $adminEditMode = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->initializeManagers();
        $this->registerListeners();
        $this->getLogger()->info("Skyblock plugin enabled!");
    }

    private function initializeManagers(): void {
        $this->dataManager = new DataManager($this);
        $this->islandManager = new IslandManager($this, $this->dataManager);
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
            case "is":
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
}