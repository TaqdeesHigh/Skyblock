<?php

declare(strict_types=1);

namespace taqdees\Skyblock\listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Chest;
use pocketmine\event\player\PlayerItemUseEvent;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\commands\AdminCommand;

class EventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($this->plugin->isInEditMode($player->getName()) && 
            $item->getTypeId() === VanillaItems::COMPASS()->getTypeId()) {
            
            $customName = $item->getCustomName();
            if ($customName === "§bSkyblock Setup Compass") {
                $event->cancel();
                $adminCommand = new AdminCommand($this->plugin);
                $adminCommand->openSetupForm($player);
                return;
            }
        }
    }

    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($this->plugin->isInEditMode($player->getName()) && 
            $item->getTypeId() === VanillaItems::COMPASS()->getTypeId()) {
            
            $customName = $item->getCustomName();
            if ($customName === "§bSkyblock Setup Compass") {
                $event->cancel();
                $adminCommand = new AdminCommand($this->plugin);
                $adminCommand->openSetupForm($player);
                return;
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $transaction = $event->getTransaction();
        $item = $event->getItem();
        $blocks = $transaction->getBlocks();
        
        foreach ($blocks as [$x, $y, $z, $block]) {
            if ($this->plugin->isInEditMode($player->getName()) && 
                $block->getTypeId() === VanillaBlocks::CHEST()->getTypeId()) {
                
                $customName = $item->getCustomName();
                if ($customName === "§bTemplate Chest") {
                    $player->sendMessage("§aTemplate chest placed! Fill it with starting items.");
                    $this->saveChestTemplate($player, $block->getPosition());
                    return;
                }
            }
            if (!$this->plugin->isInEditMode($player->getName()) && 
                !$this->plugin->getIslandManager()->isOnIsland($player, $block->getPosition())) {
                
                $skyblockWorld = $this->plugin->getDataManager()->getSkyblockWorld();
                if ($skyblockWorld !== null && 
                    $block->getPosition()->getWorld()->getFolderName() === $skyblockWorld) {
                    
                    $event->cancel();
                    $player->sendMessage("§cYou can only build on your own island!");
                    return;
                }
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        if (!$this->plugin->isInEditMode($player->getName()) && 
            !$this->plugin->getIslandManager()->isOnIsland($player, $event->getBlock()->getPosition())) {
            
            $skyblockWorld = $this->plugin->getDataManager()->getSkyblockWorld();
            if ($skyblockWorld !== null && 
                $event->getBlock()->getPosition()->getWorld()->getFolderName() === $skyblockWorld) {
                
                $event->cancel();
                $player->sendMessage("§cYou can only break blocks on your own island!");
            }
        }
    }

    private function saveChestTemplate(Player $player, \pocketmine\world\Position $position): void {
        $this->plugin->getScheduler()->scheduleDelayedTask(
            new \pocketmine\scheduler\ClosureTask(function() use ($player, $position): void {
                $tile = $position->getWorld()->getTile($position);
                if ($tile instanceof Chest) {
                    $items = [];
                    foreach ($tile->getInventory()->getContents() as $slot => $item) {
                        $items[$slot] = [
                            "id" => $item->getTypeId(),
                            "meta" => $item->getMeta(),
                            "count" => $item->getCount(),
                            "nbt" => $item->getNamedTag()->toString()
                        ];
                    }
                    
                    $this->plugin->getDataManager()->setChestTemplate($position, $items);
                    $player->sendMessage("§aChest template saved with " . count($items) . " items!");
                } else {
                    $player->sendMessage("§cFailed to save chest template - tile not found!");
                }
            }),
            20
        );
    }
}