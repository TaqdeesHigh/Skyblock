<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\npc;

use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use taqdees\Skyblock\Main;

class IslandFormManager {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function openIslandSettingsMenu(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;
            
            $islandManager = $this->plugin->getIslandManager();
            
            switch ($data) {
                case 0:
                    $this->openInviteForm($player);
                    break;
                case 1:
                    $this->openKickForm($player);
                    break;
                case 2:
                    $members = $islandManager->getMembers($player);
                    if ($members !== null) {
                        $player->sendMessage("§aIsland Members: §7" . implode(", ", $members));
                    }
                    break;
                case 3:
                    $islandManager->deleteIsland($player);
                    break;
            }
        });

        $form->setTitle("§aIsland Settings");
        $form->setContent("§7Manage your island:");
        
        $form->addButton("§eInvite Player\n§7Add someone to your island");
        $form->addButton("§cKick Player\n§7Remove someone from your island");
        $form->addButton("§bView Members\n§7See who's on your island");
        $form->addButton("§4Reset Island\n§7Delete and start over");

        $player->sendForm($form);
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