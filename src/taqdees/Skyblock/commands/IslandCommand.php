<?php

declare(strict_types=1);

namespace taqdees\Skyblock\commands;

use pocketmine\player\Player;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\managers\IslandManager;
use taqdees\Skyblock\traits\PluginOwned;

class IslandCommand {

    use PluginOwned;

    private IslandManager $islandManager;

    public function __construct(Main $plugin, IslandManager $islandManager) {
        $this->plugin = $plugin;
        $this->islandManager = $islandManager;
    }

    public function handleCommand(Player $player, array $args): bool {
        if (!$player->hasPermission("skyblock.player")) {
            $player->sendMessage("§cYou don't have permission to use this command!");
            return true;
        }

        if (empty($args)) {
            $this->sendHelpMessage($player);
            return true;
        }

        switch (strtolower($args[0])) {
            case "create":
                return $this->islandManager->createIsland($player);
                
            case "home":
                return $this->islandManager->teleportToIsland($player);
                
            case "sethome":
                return $this->islandManager->setHome($player);
            case "invite":
                if (!isset($args[1])) {
                    $player->sendMessage("§cUsage: /island invite <player>");
                    return true;
                }
                return $this->islandManager->inviteMember($player, $args[1]);
                
            case "kick":
                if (!isset($args[1])) {
                    $player->sendMessage("§cUsage: /island kick <player>");
                    return true;
                }
                return $this->islandManager->kickMember($player, $args[1]);
            case "leave":
                return $this->islandManager->leaveIsland($player);
                
            case "members":
                $members = $this->islandManager->getMembers($player);
                if ($members !== null) {
                    $player->sendMessage("§aIsland Members: §7" . implode(", ", $members));
                }
                return true;
                
            case "reset":
            case "delete":
                return $this->islandManager->deleteIsland($player);
                
            default:
                $this->sendHelpMessage($player);
                return true;
        }
    }

    private function sendHelpMessage(Player $player): void {
        $player->sendMessage("§b=== Skyblock Commands ===");
        $player->sendMessage("§7/island create §f- Create your island");
        $player->sendMessage("§7/island home §f- Teleport to your island");
        $player->sendMessage("§7/island sethome §f- Set home location");
        $player->sendMessage("§7/island invite <player> §f- Invite a player");
        $player->sendMessage("§7/island kick <player> §f- Kick a player");
        $player->sendMessage("§7/island leave §f- Leave current island");
        $player->sendMessage("§7/island members §f- List island members");
        $player->sendMessage("§7/island delete §f- Delete your island");
        $player->sendMessage("§7You can also use §b/is §7as a shortcut!");
    }
}