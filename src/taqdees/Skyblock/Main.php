<?php

declare(strict_types=1);

namespace taqdees\Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\WorldManager;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use muqsit\invmenu\InvMenuHandler;
use taqdees\Skyblock\commands\IslandCommand;
use taqdees\Skyblock\commands\AdminCommand;
use taqdees\Skyblock\listeners\EventListener;
use taqdees\Skyblock\managers\IslandManager;
use taqdees\Skyblock\managers\CraftingManager;
use taqdees\Skyblock\managers\DataManager;
use taqdees\Skyblock\managers\NPCManager;
use taqdees\Skyblock\entities\OzzyNPC;
use taqdees\Skyblock\entities\BaseMinion;
use taqdees\Skyblock\managers\MinionManager;
use taqdees\Skyblock\minions\professions\ProfessionRegistry;
use taqdees\Skyblock\minions\MinionCropHandler;
use taqdees\Skyblock\entities\MinionTypes\mining\CobblestoneMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\CoalMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\IronMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\GoldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\DiamondMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\EmeraldMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\LapisMinion;
use taqdees\Skyblock\entities\MinionTypes\mining\RedstoneMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\WheatMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\CarrotMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PotatoMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\MelonMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\PumpkinMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\OakMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\SpruceMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\BirchMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\AcaciaMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\DarkOakMinion;

class Main extends PluginBase {

    private IslandManager $islandManager;
    private DataManager $dataManager;
    private NPCManager $npcManager;
    private AdminCommand $adminCommand;
    private IslandCommand $islandCommand;
    private MinionManager $minionManager;
    private CraftingManager $craftingManager;
    private MinionCropHandler $minionCropHandler;
    private array $adminEditMode = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        
        ProfessionRegistry::init();
        
        $this->registerEntities();
        $this->registerGenerators();
        $this->initializeManagers();
        $this->registerListeners();
        
        $this->getLogger()->info("§aSkyblock plugin enabled successfully!");
        $this->getLogger()->info("§eRegistered " . $this->getRegisteredMinionCount() . " minion types");
    }

    private function registerEntities(): void {
        $entityFactory = EntityFactory::getInstance();
        
        $entityFactory->register(OzzyNPC::class, function(World $world, CompoundTag $nbt): OzzyNPC {
            return new OzzyNPC($this, EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['OzzyNPC', 'taqdees:ozzynpc']);
        
        $entityFactory->register(CobblestoneMinion::class, function(World $world, CompoundTag $nbt): CobblestoneMinion {
            return new CobblestoneMinion($this, EntityDataHelper::parseLocation($nbt, $world), "cobblestone", null, $nbt);
        }, ['CobblestoneMinion', 'taqdees:cobblestone_minion']);
        
        $entityFactory->register(CoalMinion::class, function(World $world, CompoundTag $nbt): CoalMinion {
            return new CoalMinion($this, EntityDataHelper::parseLocation($nbt, $world), "coal", null, $nbt);
        }, ['CoalMinion', 'taqdees:coal_minion']);
        
        $entityFactory->register(IronMinion::class, function(World $world, CompoundTag $nbt): IronMinion {
            return new IronMinion($this, EntityDataHelper::parseLocation($nbt, $world), "iron", null, $nbt);
        }, ['IronMinion', 'taqdees:iron_minion']);
        
        $entityFactory->register(GoldMinion::class, function(World $world, CompoundTag $nbt): GoldMinion {
            return new GoldMinion($this, EntityDataHelper::parseLocation($nbt, $world), "gold", null, $nbt);
        }, ['GoldMinion', 'taqdees:gold_minion']);
        
        $entityFactory->register(DiamondMinion::class, function(World $world, CompoundTag $nbt): DiamondMinion {
            return new DiamondMinion($this, EntityDataHelper::parseLocation($nbt, $world), "diamond", null, $nbt);
        }, ['DiamondMinion', 'taqdees:diamond_minion']);
        
        $entityFactory->register(EmeraldMinion::class, function(World $world, CompoundTag $nbt): EmeraldMinion {
            return new EmeraldMinion($this, EntityDataHelper::parseLocation($nbt, $world), "emerald", null, $nbt);
        }, ['EmeraldMinion', 'taqdees:emerald_minion']);
        
        $entityFactory->register(LapisMinion::class, function(World $world, CompoundTag $nbt): LapisMinion {
            return new LapisMinion($this, EntityDataHelper::parseLocation($nbt, $world), "lapis", null, $nbt);
        }, ['LapisMinion', 'taqdees:lapis_minion']);
        
        $entityFactory->register(RedstoneMinion::class, function(World $world, CompoundTag $nbt): RedstoneMinion {
            return new RedstoneMinion($this, EntityDataHelper::parseLocation($nbt, $world), "redstone", null, $nbt);
        }, ['RedstoneMinion', 'taqdees:redstone_minion']);
        
        $entityFactory->register(WheatMinion::class, function(World $world, CompoundTag $nbt): WheatMinion {
            return new WheatMinion($this, EntityDataHelper::parseLocation($nbt, $world), "wheat", null, $nbt);
        }, ['WheatMinion', 'taqdees:wheat_minion']);
        
        $entityFactory->register(CarrotMinion::class, function(World $world, CompoundTag $nbt): CarrotMinion {
            return new CarrotMinion($this, EntityDataHelper::parseLocation($nbt, $world), "carrot", null, $nbt);
        }, ['CarrotMinion', 'taqdees:carrot_minion']);
        
        $entityFactory->register(PotatoMinion::class, function(World $world, CompoundTag $nbt): PotatoMinion {
            return new PotatoMinion($this, EntityDataHelper::parseLocation($nbt, $world), "potato", null, $nbt);
        }, ['PotatoMinion', 'taqdees:potato_minion']);
        
        $entityFactory->register(MelonMinion::class, function(World $world, CompoundTag $nbt): MelonMinion {
            return new MelonMinion($this, EntityDataHelper::parseLocation($nbt, $world), "melon", null, $nbt);
        }, ['MelonMinion', 'taqdees:melon_minion']);
        
        $entityFactory->register(PumpkinMinion::class, function(World $world, CompoundTag $nbt): PumpkinMinion {
            return new PumpkinMinion($this, EntityDataHelper::parseLocation($nbt, $world), "pumpkin", null, $nbt);
        }, ['PumpkinMinion', 'taqdees:pumpkin_minion']);
        
        $entityFactory->register(OakMinion::class, function(World $world, CompoundTag $nbt): OakMinion {
            return new OakMinion($this, EntityDataHelper::parseLocation($nbt, $world), "oak", null, $nbt);
        }, ['OakMinion', 'taqdees:oak_minion']);
        
        $entityFactory->register(SpruceMinion::class, function(World $world, CompoundTag $nbt): SpruceMinion {
            return new SpruceMinion($this, EntityDataHelper::parseLocation($nbt, $world), "spruce", null, $nbt);
        }, ['SpruceMinion', 'taqdees:spruce_minion']);
        
        $entityFactory->register(BirchMinion::class, function(World $world, CompoundTag $nbt): BirchMinion {
            return new BirchMinion($this, EntityDataHelper::parseLocation($nbt, $world), "birch", null, $nbt);
        }, ['BirchMinion', 'taqdees:birch_minion']);
        
        $entityFactory->register(AcaciaMinion::class, function(World $world, CompoundTag $nbt): AcaciaMinion {
            return new AcaciaMinion($this, EntityDataHelper::parseLocation($nbt, $world), "acacia", null, $nbt);
        }, ['AcaciaMinion', 'taqdees:acacia_minion']);
        
        $entityFactory->register(DarkOakMinion::class, function(World $world, CompoundTag $nbt): DarkOakMinion {
            return new DarkOakMinion($this, EntityDataHelper::parseLocation($nbt, $world), "dark_oak", null, $nbt);
        }, ['DarkOakMinion', 'taqdees:dark_oak_minion']);
    }

    private function registerGenerators(): void {
        \taqdees\Skyblock\generators\VoidWorldGenerator::register();
    }
    
    private function initializeManagers(): void {
        $this->dataManager = new DataManager($this);
        $this->islandManager = new IslandManager($this, $this->dataManager);
        $this->npcManager = new NPCManager($this);
        $this->adminCommand = new AdminCommand($this);
        $this->islandCommand = new IslandCommand($this, $this->islandManager);
        $this->minionCropHandler = new MinionCropHandler($this);
        $this->minionManager = new MinionManager($this);
        $this->craftingManager = new CraftingManager($this); 
    }

    private function registerListeners(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }
    
    private function getRegisteredMinionCount(): int {
        return 18;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can only be used in-game!");
            return true;
        }

        switch ($command->getName()) {
            case "skyblock":
                return $this->adminCommand->handleCommand($sender, $args);
            case "island":
                return $this->islandCommand->handleCommand($sender, $args);
        }
        return false;
    }

    public function getIslandManager(): IslandManager {
        return $this->islandManager;
    }

    public function getDataManager(): DataManager {
        return $this->dataManager;
    }

    public function getNPCManager(): NPCManager {
        return $this->npcManager;
    }

    public function isInEditMode(string $playerName): bool {
        return isset($this->adminEditMode[$playerName]);
    }

    public function setEditMode(string $playerName, bool $enabled): void {
        if ($enabled) {
            $this->adminEditMode[$playerName] = true;
        } else {
            unset($this->adminEditMode[$playerName]);
        }
    }

    public function getAdminEditMode(): array {
        return $this->adminEditMode;
    }

    public function getMinionManager(): MinionManager {
        return $this->minionManager;
    }

    public function getCraftingManager(): CraftingManager {
        return $this->craftingManager;
    }

    public function getMinionCropHandler(): MinionCropHandler {
        return $this->minionCropHandler;
    }

    public function getConfigValue(string $path, $default = null) {
        $keys = explode('.', $path);
        $value = $this->getConfig()->getAll();
        
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }
            $value = $value[$key];
        }
        
        return $value;
    }

    public function onDisable(): void {
        $this->getLogger()->info("§eSaving all minion data...");
        
        $minionCount = 0;
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof BaseMinion) {
                    $entity->onServerShutdown();
                    $minionCount++;
                }
            }
        }
        
        $this->getLogger()->info("§aSaved " . $minionCount . " minions successfully!");
        $this->getLogger()->info("§aSkyblock plugin disabled!");
    }
}