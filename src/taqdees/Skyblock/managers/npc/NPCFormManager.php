<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\npc;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
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
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName("§6" . $npc->getDisplayName() . "'s Menu");
        
        $inventory = $menu->getInventory();
        $nameTag = VanillaItems::NAME_TAG();
        $nameTag->setCustomName("§eChange " . $npc->getDisplayName() . "'s Name");
        $nameTag->setLore([
            "§7Customize your NPC's name",
            "§7Click to change!"
        ]);
        $inventory->setItem(11, $nameTag);
        $enderPearl = VanillaItems::ENDER_PEARL();
        $enderPearl->setCustomName("§bChange " . $npc->getDisplayName() . "'s Location");
        $enderPearl->setLore([
            "§7Move your NPC to a new position",
            "§7Click to get location egg!"
        ]);
        $inventory->setItem(12, $enderPearl);
        $grass = VanillaBlocks::GRASS()->asItem();
        $grass->setCustomName("§aIsland Settings");
        $grass->setLore([
            "§7Manage your island",
            "§7Invite players, kick members, etc.",
            "§7Click to open island menu!"
        ]);
        $inventory->setItem(13, $grass);
        $compass = VanillaItems::COMPASS();
        $compass->setCustomName("§dGo To Skyblock Hub");
        $compass->setLore([
            "§7Fast travel to the hub",
            "§7Click to teleport!"
        ]);
        $inventory->setItem(14, $compass);
        $barrier = VanillaBlocks::BARRIER()->asItem();
        $barrier->setCustomName("§cClose Menu");
        $barrier->setLore([
            "§7Close this menu"
        ]);
        $inventory->setItem(15, $barrier);

        $menu->setListener(function(InvMenuTransaction $transaction) use ($npc): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $slot = $transaction->getAction()->getSlot();
            
            switch ($slot) {
                case 11:
                    $this->openNameChangeForm($player, $npc);
                    break;
                case 12:
                    $this->spawnManager->startLocationChangeMode($player, $npc);
                    $player->removeCurrentWindow();
                    break;
                case 13:
                    $this->islandFormManager->openIslandSettingsMenu($player);
                    break;
                case 14:
                    $this->teleportToHub($player);
                    $player->removeCurrentWindow();
                    break;
                case 15:
                    $player->removeCurrentWindow();
                    break;
            }
            
            return $transaction->discard();
        });

        $menu->send($player);
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