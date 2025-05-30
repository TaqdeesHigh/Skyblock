<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\npc;

use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\OzzyNPC;

class NPCFormManager {

    private Main $plugin;
    private NPCSpawnManager $spawnManager;
    private IslandFormManager $islandFormManager;

    public function __construct(Main $plugin, NPCSpawnManager $spawnManager) {
        $this->plugin = $plugin;
        $this->spawnManager = $spawnManager;
        $this->islandFormManager = new IslandFormManager($plugin);
    }

    public function openNPCMenu(Player $player, OzzyNPC $npc): void {
        $form = new SimpleForm(function (Player $player, ?int $data) use ($npc) {
            if ($data === null) return;
            
            switch ($data) {
                case 0:
                    $this->openNameChangeForm($player, $npc);
                    break;
                case 1:
                    $this->spawnManager->startLocationChangeMode($player, $npc);
                    break;
                case 2:
                    $this->islandFormManager->openIslandSettingsMenu($player);
                    break;
                case 3:
                    $this->teleportToHub($player);
                    break;
            }
        });

        $npcName = $npc->getDisplayName();
        $form->setTitle("§6" . $npcName . "'s Menu");
        $form->setContent("§7What would you like to do?");
        
        $form->addButton("§eChange " . $npcName . "'s Name\n§7Customize your NPC");
        $form->addButton("§bChange " . $npcName . "'s Location\n§7Move your NPC");
        $form->addButton("§aIsland Settings\n§7Manage your island");
        $form->addButton("§dGo To Skyblock Hub\n§7Fast travel");

        $player->sendForm($form);
    }

    private function openNameChangeForm(Player $player, OzzyNPC $npc): void {
        $form = new CustomForm(function (Player $player, ?array $data) use ($npc) {
            if ($data === null) return;
            
            $newName = trim($data[0]);
            if (empty($newName)) {
                $player->sendMessage("§cName cannot be empty!");
                return;
            }
            
            if (strlen($newName) > 20) {
                $player->sendMessage("§cName is too long! Maximum 20 characters.");
                return;
            }
            
            $this->spawnManager->updateNPCName($player->getName(), $newName);
            $player->sendMessage("§aName changed to: §6" . $newName);
        });

        $form->setTitle("§6Change NPC Name");
        $form->addInput("§7Enter new name for your NPC:", $npc->getDisplayName(), $npc->getDisplayName());
        
        $player->sendForm($form);
    }

    private function teleportToHub(Player $player): void {
        $defaultWorld = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld !== null) {
            $player->teleport($defaultWorld->getSpawnLocation());
            $player->sendMessage("§aWelcome to the Skyblock Hub!");
        } else {
            $player->sendMessage("§cHub world not found!");
        }
    }
}