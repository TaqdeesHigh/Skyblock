<?php

declare(strict_types=1);

namespace taqdees\Skyblock\commands;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use taqdees\Skyblock\Main;

class AdminCommand {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function handleCommand(Player $player, array $args): bool {
        if (!$player->hasPermission("skyblock.admin")) {
            $player->sendMessage("§cYou don't have permission to use this command!");
            return true;
        }

        if (empty($args) || $args[0] !== "admin") {
            $player->sendMessage("§cUsage: /skyblock admin");
            return true;
        }

        $this->enterEditMode($player);
        return true;
    }

    private function enterEditMode(Player $player): void {
        $this->plugin->setEditMode($player->getName(), true);
        $player->getInventory()->clearAll();
        $compass = VanillaItems::COMPASS();
        $compass->setCustomName("§bSkyblock Setup Compass");
        $compass->setLore(["§7Right-click to open setup menu"]);
        
        $player->getInventory()->setItem(0, $compass);
        $player->sendMessage("§aEntered Skyblock Edit Mode!");
        $player->sendMessage("§7Right-click the compass to open the setup menu.");
    }

    public function openSetupForm(Player $player): void {
        if (!$this->plugin->isInEditMode($player->getName())) {
            $player->sendMessage("§cYou are not in edit mode!");
            return;
        }

        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;
            
            switch ($data) {
                case 0:
                    $this->openWorldSelectionForm($player);
                    break;
                case 1:
                    $this->exitEditMode($player);
                    break;
            }
        });

        $form->setTitle("§bSkyblock Setup");
        $form->setContent("§7Configure your Skyblock world settings:");
        
        $form->addButton("§aSetup Skyblock World\n§7Choose a world for Skyblock");
        
        $skyblockWorld = $this->plugin->getDataManager()->getSkyblockWorld();
        if ($skyblockWorld !== null && $skyblockWorld !== "") {
            $form->addButton("§cDone\n§7Exit setup mode");
        } else {
            $form->addButton("§8Done\n§7(Select a world first)");
        }

        $player->sendForm($form);
    }

    private function openWorldSelectionForm(Player $player): void {
        $worldManager = $this->plugin->getServer()->getWorldManager();
        $worlds = $worldManager->getWorlds();
        $worldNames = [];
        
        foreach ($worlds as $world) {
            $worldNames[] = $world->getFolderName();
        }

        if (empty($worldNames)) {
            $player->sendMessage("§cNo worlds found!");
            return;
        }

        $form = new CustomForm(function (Player $player, ?array $data) use ($worldNames) {
            if ($data === null) return;
            
            $selectedWorldIndex = (int)$data[0];
            if (!isset($worldNames[$selectedWorldIndex])) {
                $player->sendMessage("§cInvalid world selection!");
                return;
            }
            
            $selectedWorld = $worldNames[$selectedWorldIndex];
            $this->setupSkyblockWorld($player, $selectedWorld);
        });

        $form->setTitle("§bSelect Skyblock World");
        $form->addDropdown("§7Choose a world to use for Skyblock:", $worldNames);
        $form->addLabel("§7This will set the selected world as your Skyblock world.\n§7Make sure the world contains pre-built islands!");

        $player->sendForm($form);
    }

    private function setupSkyblockWorld(Player $player, string $worldName): void {
        $worldManager = $this->plugin->getServer()->getWorldManager();
        $world = $worldManager->getWorldByName($worldName);
        
        if ($world === null) {
            $player->sendMessage("§cWorld '$worldName' not found!");
            return;
        }

        $this->plugin->getDataManager()->setSkyblockWorld($worldName);
        $player->sendMessage("§aWorld '$worldName' has been set as the Skyblock world!");
        $chestBlock = VanillaBlocks::CHEST()->asItem();
        $chestBlock->setCustomName("§bTemplate Chest");
        $chestBlock->setLore([
            "§7Place this chest to set the template",
            "§7location and fill it with items",
            "§7that new players will receive"
        ]);
        
        $player->getInventory()->addItem($chestBlock);
        $player->sendMessage("§7Place the template chest and fill it with starting items.");
        $player->teleport($world->getSpawnLocation());
        $player->sendMessage("§aTeleported to the Skyblock world!");
    }

    private function exitEditMode(Player $player): void {
        $skyblockWorld = $this->plugin->getDataManager()->getSkyblockWorld();
        if ($skyblockWorld === null || $skyblockWorld === "") {
            $player->sendMessage("§cPlease setup a Skyblock world first!");
            $this->openSetupForm($player);
            return;
        }
        
        $this->plugin->setEditMode($player->getName(), false);
        $player->sendMessage("§aExited Skyblock Edit Mode!");
        $player->getInventory()->clearAll();
        
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($skyblockWorld);
        if ($world !== null) {
            $player->teleport($world->getSpawnLocation());
            $player->sendMessage("§aTeleported to Skyblock world!");
        } else {
            $player->sendMessage("§cSkyblock world not found! Make sure the world is loaded.");
        }
    }
}