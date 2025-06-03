<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers\npc;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\OzzyNPC;

class NPCIntroductionManager {

    private Main $plugin;
    /** @var array<string, bool> */
    private array $hasSeenIntroduction = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function showIntroduction(Player $player, OzzyNPC $npc, callable $onComplete): void {
        $playerName = $player->getName();
        
        if ($this->hasSeenIntroduction($playerName)) {
            call_user_func($onComplete);
            return;
        }

        $this->hasSeenIntroduction[$playerName] = true;
        
        $introMessages = [
            "§6Hello there, " . $player->getName() . "!",
            "§eI'm " . $npc->getDisplayName() . ", your island assistant!",
            "§bI can help you manage your island,",
            "§binvite friends, and much more!",
            "§aLet me show you what I can do..."
        ];

        $this->sendMessageSequence($player, $introMessages, 0, $onComplete);
    }

    private function sendMessageSequence(Player $player, array $messages, int $index, callable $onComplete): void {
        if ($index >= count($messages)) {
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($onComplete): void {
                call_user_func($onComplete);
            }), 20);
            return;
        }
        $player->sendMessage($messages[$index]);
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $messages, $index, $onComplete): void {
            $this->sendMessageSequence($player, $messages, $index + 1, $onComplete);
        }), 30);
    }

    public function hasSeenIntroduction(string $playerName): bool {
        return isset($this->hasSeenIntroduction[$playerName]);
    }

    public function resetIntroduction(string $playerName): void {
        unset($this->hasSeenIntroduction[$playerName]);
    }
}