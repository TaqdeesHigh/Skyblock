<?php

declare(strict_types=1);

namespace taqdees\Skyblock\commands;

use pocketmine\player\Player;
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

        $player->sendMessage("§eSkyhub Setup Coming Soon!");
        return true;
    }
}