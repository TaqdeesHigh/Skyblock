<?php

declare(strict_types=1);

namespace taqdees\Skyblock\commands;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\world\Position;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\traits\PluginOwned;

class AdminCommand {

    use PluginOwned;

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
        $ozzyEgg = $this->plugin->getNPCManager()->createOzzyEgg();
        $player->getInventory()->setItem(0, $compass);
        $player->getInventory()->setItem(1, $ozzyEgg);
        $player->sendMessage("§aEntered Skyblock Edit Mode!");
        $player->sendMessage("§7Right-click the compass to open the setup menu.");
        $player->sendMessage("§7Right-click Ozzy's Egg to spawn your NPC assistant!");
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
                    $this->setPlayerPosition($player);
                    break;
                case 2:
                    $this->exitEditMode($player);
                    break;
            }
        });

        $form->setTitle("Skyblock Setup");
        $form->setContent("Configure your Skyblock world settings:");
        
        $form->addButton("Setup Skyblock World\nChoose a world for Skyblock");
        $form->addButton("Set Player Position\nSet spawn position for new islands");
        
        $skyblockWorld = $this->plugin->getDataManager()->getSkyblockWorld();
        $playerPosition = $this->plugin->getDataManager()->getPlayerSpawnPosition();
        
        if ($skyblockWorld !== null && $skyblockWorld !== "" && $playerPosition !== null) {
            $form->addButton("Done\nExit setup mode");
        } else {
            $form->addButton("Done\n(Complete setup first)");
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

        $form->setTitle("Select Skyblock World");
        $form->addDropdown("Choose a world to use for Skyblock:", $worldNames);
        $form->addLabel("This will set the selected world as your Skyblock world.\nMake sure the world contains pre-built islands!");

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
        $player->sendMessage("§7You can now set the player spawn position and then exit edit mode.");
        $player->teleport($world->getSpawnLocation());
        $player->sendMessage("§aTeleported to the Skyblock world for verification!");
    }

    private function setPlayerPosition(Player $player): void {
        $position = $player->getPosition();
        $this->plugin->getDataManager()->setPlayerSpawnPosition($position);
        $player->sendMessage("§aPlayer spawn position has been set!");
        $player->sendMessage("§7Position: X=" . round($position->getX(), 2) . 
                           " Y=" . round($position->getY(), 2) . 
                           " Z=" . round($position->getZ(), 2) . 
                           " World=" . $position->getWorld()->getFolderName());
        $player->sendMessage("§7New islands will spawn players at this location.");
    }

    private function exitEditMode(Player $player): void {
        $skyblockWorld = $this->plugin->getDataManager()->getSkyblockWorld();
        $playerPosition = $this->plugin->getDataManager()->getPlayerSpawnPosition();
        
        if ($skyblockWorld === null || $skyblockWorld === "") {
            $player->sendMessage("§cPlease setup a Skyblock world first!");
            $this->openSetupForm($player);
            return;
        }
        
        if ($playerPosition === null) {
            $player->sendMessage("§cPlease set the player spawn position first!");
            $this->openSetupForm($player);
            return;
        }
        
        $this->plugin->setEditMode($player->getName(), false);
        $player->sendMessage("§aExited Skyblock Edit Mode!");
        $player->getInventory()->clearAll();
        $defaultWorld = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld !== null) {
            $player->teleport($defaultWorld->getSpawnLocation());
            $player->sendMessage("§aTeleported back to the default world!");
        } else {
            $player->sendMessage("§eSetup complete! Default world not found for teleportation.");
        }
    }
}