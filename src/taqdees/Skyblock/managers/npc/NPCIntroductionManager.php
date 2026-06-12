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
    /** @var array<string, bool> */
    private array $playingIntroduction = [];
    /** @var array<string, int> */
    private array $introductionStartTime = [];
    /** @var array<string, callable> */
    private array $pendingCallbacks = [];

    private string $dataFile;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->dataFile = $plugin->getDataFolder() . "seen_introductions.json";
        $this->loadFromDisk();
    }

    private function loadFromDisk(): void {
        if (!file_exists($this->dataFile)) {
            return;
        }

        $json = file_get_contents($this->dataFile);
        if ($json === false) return;

        $data = json_decode($json, true);
        if (!is_array($data)) return;

        foreach ($data as $playerName) {
            $this->hasSeenIntroduction[$playerName] = true;
        }
    }

    private function saveToDisk(): void {
        $data = array_keys($this->hasSeenIntroduction);
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function showIntroduction(Player $player, OzzyNPC $npc, callable $onComplete): void {
        $playerName = $player->getName();

        if (isset($this->playingIntroduction[$playerName])) {
            $this->pendingCallbacks[$playerName] = $onComplete;
            return;
        }

        if ($this->hasSeenIntroduction($playerName)) {
            call_user_func($onComplete);
            return;
        }

        $this->playingIntroduction[$playerName] = true;
        $this->introductionStartTime[$playerName] = time();
        $this->pendingCallbacks[$playerName] = $onComplete;
        $this->hasSeenIntroduction[$playerName] = true;
        $this->saveToDisk();

        $introMessages = [
            "§6Hello there, " . $player->getName() . "!",
            "§eI'm " . $npc->getDisplayName() . ", your island assistant!",
            "§bI can help you manage your island,",
            "§binvite friends, and much more!",
            "§aLet me show you what I can do..."
        ];

        $this->sendMessageSequence($player, $introMessages, 0);
    }

    public function resetIntroduction(string $playerName): void {
        $this->cleanupIntroduction($playerName);
        unset($this->hasSeenIntroduction[$playerName]);
        $this->saveToDisk();
    }

    public function hasSeenIntroduction(string $playerName): bool {
        return isset($this->hasSeenIntroduction[$playerName]);
    }

    public function isPlayingIntroduction(string $playerName): bool {
        return isset($this->playingIntroduction[$playerName]);
    }

    public function stopIntroduction(string $playerName): void {
        if (!$this->isPlayingIntroduction($playerName)) {
            $this->cleanupIntroduction($playerName);
        }
    }

    private function sendMessageSequence(Player $player, array $messages, int $index): void {
        $playerName = $player->getName();

        if (!$player->isOnline() || !isset($this->playingIntroduction[$playerName])) {
            $this->completeIntroduction($playerName);
            return;
        }
        if (isset($this->introductionStartTime[$playerName]) &&
            (time() - $this->introductionStartTime[$playerName]) > 30) {
            $this->completeIntroduction($playerName);
            return;
        }

        if ($index >= count($messages)) {
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($playerName): void {
                $this->completeIntroduction($playerName);
            }), 20);
            return;
        }

        $player->sendMessage($messages[$index]);
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $messages, $index): void {
            $this->sendMessageSequence($player, $messages, $index + 1);
        }), 30);
    }

    private function completeIntroduction(string $playerName): void {
        if (isset($this->pendingCallbacks[$playerName])) {
            $callback = $this->pendingCallbacks[$playerName];
            $this->cleanupIntroduction($playerName);
            call_user_func($callback);
        } else {
            $this->cleanupIntroduction($playerName);
        }
    }

    private function cleanupIntroduction(string $playerName): void {
        unset($this->playingIntroduction[$playerName]);
        unset($this->introductionStartTime[$playerName]);
        unset($this->pendingCallbacks[$playerName]);
    }
}