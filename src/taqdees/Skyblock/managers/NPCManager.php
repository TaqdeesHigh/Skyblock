<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use taqdees\Skyblock\Main;
use taqdees\Skyblock\entities\OzzyNPC;

class NPCManager {

    private Main $plugin;
    private Config $npcConfig;
    /** @var array<string, OzzyNPC> */
    private array $npcs = [];
    /** @var array<string, bool> */
    private array $placingMode = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->initializeConfig();
        $this->loadNPCs();
    }

    private function initializeConfig(): void {
        $this->npcConfig = new Config($this->plugin->getDataFolder() . "npcs.yml", Config::YAML, [
            "npcs" => []
        ]);
    }

    public function createOzzyEgg(): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§6Ozzy's Egg");
        $egg->setLore([
            "§7Right-click to spawn Ozzy NPC",
            "§7Your island's helpful assistant"
        ]);
        return $egg;
    }

    public function spawnNPC(Player $player, Position $position): bool {
        if (isset($this->npcs[$player->getName()])) {
            $player->sendMessage("§cYou already have an Ozzy NPC! Remove the old one first.");
            return false;
        }
        
        $location = new \pocketmine\entity\Location(
            $position->getX(),
            $position->getY(),
            $position->getZ(),
            $position->getWorld(),
            0,
            0
        );

        $npc = new OzzyNPC($this->plugin, $location);
        $npc->spawnToAll();
        
        $this->npcs[$player->getName()] = $npc;
        $this->saveNPCData($player->getName(), $position, "Ozzy");
        
        $player->sendMessage("§aOzzy has been spawned! Right-click him to interact.");
        return true;
    }

    public function removeNPC(string $playerName): bool {
        if (!isset($this->npcs[$playerName])) {
            return false;
        }

        $npc = $this->npcs[$playerName];
        $npc->flagForDespawn();
        unset($this->npcs[$playerName]);
        
        $npcs = $this->npcConfig->get("npcs", []);
        unset($npcs[$playerName]);
        $this->npcConfig->set("npcs", $npcs);
        $this->npcConfig->save();
        
        return true;
    }

    public function openNPCMenu(Player $player, OzzyNPC $npc): void {
        $form = new SimpleForm(function (Player $player, ?int $data) use ($npc) {
            if ($data === null) return;
            
            switch ($data) {
                case 0:
                    $this->openNameChangeForm($player, $npc);
                    break;
                case 1:
                    $this->startLocationChangeMode($player, $npc);
                    break;
                case 2:
                    $this->openIslandSettingsMenu($player);
                    break;
                case 3:
                    $this->teleportToHub($player);
                    break;
            }
        });

        $npcName = $npc->getDisplayName();
        $form->setTitle("§6" . $npcName . "'s Menu");
        $form->setContent("§7What would you like to do?");
        
        $form->addButton("§eChange " . $npcName . "'s Name\n§7Customize your NPC");
        $form->addButton("§bChange " . $npcName . "'s Location\n§7Move your NPC");
        $form->addButton("§aIsland Settings\n§7Manage your island");
        $form->addButton("§dGo To Skyblock Hub\n§7Fast travel");

        $player->sendForm($form);
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
            
            $npc->setDisplayName($newName);
            $this->updateNPCName($player->getName(), $newName);
            $player->sendMessage("§aName changed to: §6" . $newName);
        });

        $form->setTitle("§6Change NPC Name");
        $form->addInput("§7Enter new name for your NPC:", $npc->getDisplayName(), $npc->getDisplayName());
        
        $player->sendForm($form);
    }

    private function startLocationChangeMode(Player $player, OzzyNPC $npc): void {
        $this->placingMode[$player->getName()] = true;
        
        $egg = $this->createLocationEgg();
        $player->getInventory()->addItem($egg);
        
        $player->sendMessage("§aYou received a location egg!");
        $player->sendMessage("§7Right-click where you want to place " . $npc->getDisplayName() . ".");
    }

    private function createLocationEgg(): \pocketmine\item\Item {
        $egg = VanillaItems::VILLAGER_SPAWN_EGG();
        $egg->setCustomName("§bLocation Egg");
        $egg->setLore([
            "§7Right-click to set new NPC location"
        ]);
        return $egg;
    }

    public function handleLocationEggUse(Player $player, Position $position): bool {
        if (!isset($this->placingMode[$player->getName()])) {
            return false;
        }

        if (!isset($this->npcs[$player->getName()])) {
            $player->sendMessage("§cYou don't have an NPC to move!");
            unset($this->placingMode[$player->getName()]);
            return false;
        }

        $npc = $this->npcs[$player->getName()];
        $npc->teleport($position);
        
        $this->updateNPCLocation($player->getName(), $position);
        unset($this->placingMode[$player->getName()]);
        
        $player->sendMessage("§a" . $npc->getDisplayName() . " has been moved to the new location!");
        return true;
    }

    public function isInPlacingMode(string $playerName): bool {
        return isset($this->placingMode[$playerName]);
    }

    private function openIslandSettingsMenu(Player $player): void {
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

    private function teleportToHub(Player $player): void {
        $defaultWorld = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld !== null) {
            $player->teleport($defaultWorld->getSpawnLocation());
            $player->sendMessage("§aWelcome to the Skyblock Hub!");
        } else {
            $player->sendMessage("§cHub world not found!");
        }
    }

    private function saveNPCData(string $playerName, Position $position, string $name): void {
        $npcs = $this->npcConfig->get("npcs", []);
        $npcs[$playerName] = [
            "name" => $name,
            "position" => [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ]
        ];
        
        $this->npcConfig->set("npcs", $npcs);
        $this->npcConfig->save();
    }

    private function updateNPCName(string $playerName, string $name): void {
        $npcs = $this->npcConfig->get("npcs", []);
        if (isset($npcs[$playerName])) {
            $npcs[$playerName]["name"] = $name;
            $this->npcConfig->set("npcs", $npcs);
            $this->npcConfig->save();
        }
    }

    private function updateNPCLocation(string $playerName, Position $position): void {
        $npcs = $this->npcConfig->get("npcs", []);
        if (isset($npcs[$playerName])) {
            $npcs[$playerName]["position"] = [
                "x" => $position->getX(),
                "y" => $position->getY(),
                "z" => $position->getZ(),
                "world" => $position->getWorld()->getFolderName()
            ];
            $this->npcConfig->set("npcs", $npcs);
            $this->npcConfig->save();
        }
    }

    private function loadNPCs(): void {
        $npcs = $this->npcConfig->get("npcs", []);
        
        foreach ($npcs as $playerName => $npcData) {
            $worldName = $npcData["position"]["world"];
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
            
            if ($world === null) continue;
            
            $location = new \pocketmine\entity\Location(
                $npcData["position"]["x"],
                $npcData["position"]["y"],
                $npcData["position"]["z"],
                $world,
                0,
                0
            );
            
            $npc = new OzzyNPC($this->plugin, $location);
            $npc->spawnToAll();
            $npc->setDisplayName($npcData["name"]);
            
            $this->npcs[$playerName] = $npc;
        }
    }

    public function getNPC(string $playerName): ?OzzyNPC {
        return $this->npcs[$playerName] ?? null;
    }
}