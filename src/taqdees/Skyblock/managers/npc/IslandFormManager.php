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

class IslandFormManager {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function openIslandSettingsMenu(Player $player): void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName("§aIsland Settings");
        
        $inventory = $menu->getInventory();
        $emerald = VanillaItems::EMERALD();
        $emerald->setCustomName("§eInvite Player");
        $emerald->setLore([
            "§7Add someone to your island",
            "§7Click to invite!"
        ]);
        $inventory->setItem(10, $emerald);
        $redstone = VanillaItems::REDSTONE_DUST();
        $redstone->setCustomName("§cKick Player");
        $redstone->setLore([
            "§7Remove someone from your island",
            "§7Click to kick!"
        ]);
        $inventory->setItem(12, $redstone);
        $book = VanillaItems::BOOK();
        $book->setCustomName("§bView Members");
        $book->setLore([
            "§7See who's on your island",
            "§7Click to view!"
        ]);
        $inventory->setItem(14, $book);
        $tnt = VanillaBlocks::TNT()->asItem();
        $tnt->setCustomName("§4Reset Island");
        $tnt->setLore([
            "§7Delete and start over",
            "§c§lWARNING: This cannot be undone!",
            "§7Click to reset!"
        ]);
        $inventory->setItem(16, $tnt);

        $menu->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $slot = $transaction->getAction()->getSlot();
            
            $islandManager = $this->plugin->getIslandManager();
            
            switch ($slot) {
                case 10:
                    $this->openInviteForm($player);
                    break;
                case 12:
                    $this->openKickForm($player);
                    break;
                case 14:
                    $members = $islandManager->getMembers($player);
                    if ($members !== null) {
                        $player->sendMessage("§aIsland Members: §7" . implode(", ", $members));
                    }
                    $player->removeCurrentWindow();
                    break;
                case 16:
                    $islandManager->deleteIsland($player);
                    $player->removeCurrentWindow();
                    break;
            }
            
            return $transaction->discard();
        });

        $menu->send($player);
    }

    private function openInviteForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) return;
            
            $playerName = trim($data[0]);
            if (empty($playerName)) {
                $player->sendMessage("§cPlayer name cannot be empty!");
                return;
            }
            
            $this->plugin->getIslandManager()->inviteMember($player, $playerName);
        });

        $form->setTitle("§eInvite Player");
        $form->addInput("§7Enter player name to invite:", "PlayerName");
        
        $player->sendForm($form);
    }

    private function openKickForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) return;
            
            $playerName = trim($data[0]);
            if (empty($playerName)) {
                $player->sendMessage("§cPlayer name cannot be empty!");
                return;
            }
            
            $this->plugin->getIslandManager()->kickMember($player, $playerName);
        });

        $form->setTitle("§cKick Player");
        $form->addInput("§7Enter player name to kick:", "PlayerName");
        
        $player->sendForm($form);
    }
}