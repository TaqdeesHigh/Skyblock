<?php

declare(strict_types=1);

namespace taqdees\Skyblock\listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\block\Air;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\OzzyNPC;

class EventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $action = $event->getAction();
        $block = $event->getBlock();
        
        if ($block->getTypeId() === VanillaBlocks::CRAFTING_TABLE()->getTypeId()) {
            $event->cancel();
            $this->plugin->getCraftingManager()->openCraftingUI($player);
            return;
        }
        
        if ($item->getTypeId() === VanillaItems::VILLAGER_SPAWN_EGG()->getTypeId()) {
            $customName = $item->getCustomName();

            // THIS FUNCTION WILL BE REMOVED!
            //------------
            if ($customName === "§6Ozzy's Egg" && $this->plugin->isInEditMode($player->getName())) {
                $event->cancel();
                $spawnPosition = $this->getValidSpawnPosition($block);
                if ($spawnPosition === null) {
                    $player->sendMessage("§cCannot place NPC here! Make sure there's enough space above the block.");
                    return;
                }
                
                if ($this->plugin->getNPCManager()->spawnNPC($player, $spawnPosition)) {
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                }
                return;
            }
            //------------

            $minionType = $item->getNamedTag()->getString("minionType", "");
            
            if (!empty($minionType) && $this->plugin->isInEditMode($player->getName())) {
                $event->cancel();
                $spawnPosition = $this->getValidSpawnPosition($block);
                if ($spawnPosition === null) {
                    $player->sendMessage("§cCannot place minion here! Make sure there's enough space above the block.");
                    return;
                }
                
                if ($this->plugin->getMinionManager()->spawnMinion($player, $spawnPosition, $minionType)) {
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                }
                return;
            }
            $nbt = $item->getNamedTag();
            
            if ($nbt->getString("minion_egg", "") === "true") {
                $event->cancel();
                
                $spawnPosition = $this->getValidSpawnPosition($block);
                if ($spawnPosition === null) {
                    $player->sendMessage("§cCannot place minion here! Make sure there's enough space above the block.");
                    return;
                }
                if ($this->plugin->getMinionManager()->spawnMinionFromEgg($player, $spawnPosition, $item)) {
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                }
                return;
            }
            if ($customName === "§bLocation Egg" && $this->plugin->getNPCManager()->isInPlacingMode($player->getName())) {
                $event->cancel();
                $spawnPosition = $this->getValidSpawnPosition($block);
                if ($spawnPosition === null) {
                    $player->sendMessage("§cCannot place location marker here! Make sure there's enough space above the block.");
                    return;
                }
                
                if ($this->plugin->getNPCManager()->handleLocationEggUse($player, $spawnPosition)) {
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                }
                return;
            }
        }
    }
    private function getValidSpawnPosition(\pocketmine\block\Block $block): ?\pocketmine\world\Position {
        $blockPos = $block->getPosition();
        $world = $blockPos->getWorld();
        if ($block instanceof Air) {
            return null;
        }
        $spawnX = floor($blockPos->getX()) + 0.5;
        $spawnY = $blockPos->getY() + 1;
        $spawnZ = floor($blockPos->getZ()) + 0.5;
        $checkPos1 = new \pocketmine\world\Position($spawnX, $spawnY, $spawnZ, $world);
        $checkPos2 = new \pocketmine\world\Position($spawnX, $spawnY + 1, $spawnZ, $world);
        
        $block1 = $world->getBlock($checkPos1);
        $block2 = $world->getBlock($checkPos2);
        if (!($block1 instanceof Air) || !($block2 instanceof Air)) {
            return null;
        }
        
        return new \pocketmine\world\Position($spawnX, $spawnY, $spawnZ, $world);
    }

    public function onEntitySpawn(EntitySpawnEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof OzzyNPC) {
            $this->plugin->getLogger()->info("Ozzy NPC spawned at " . $entity->getPosition());
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $transaction = $event->getTransaction();
        $blocks = $transaction->getBlocks();
        
        foreach ($blocks as [$x, $y, $z, $block]) {
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
    
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $playerName = $event->getPlayer()->getName();
        $this->plugin->getNPCManager()->cleanupPlayer($playerName);
        $this->plugin->setEditMode($playerName, false);
    }
    
    public function onPlayerLogin(PlayerLoginEvent $event): void {
        $playerName = $event->getPlayer()->getName();
        $this->plugin->getNPCManager()->cleanupPlayer($playerName);
    }
}