<?php

declare(strict_types=1);

namespace taqdees\Skyblock\managers;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\Server;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\block\tile\Chest;
use taqdees\Skyblock\Main;

class IslandManager {

    private Main $plugin;
    private DataManager $dataManager;

    public function __construct(Main $plugin, DataManager $dataManager) {
        $this->plugin = $plugin;
        $this->dataManager = $dataManager;
    }

    public function createIsland(Player $player): bool {
        if ($this->dataManager->hasIsland($player->getName())) {
            $player->sendMessage("§cYou already have an island!");
            return false;
        }

        $skyblockWorld = $this->dataManager->getSkyblockWorld();
        if ($skyblockWorld === null) {
            $player->sendMessage("§cSkyblock world is not set up yet!");
            return false;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($skyblockWorld);
        if ($world === null) {
            $player->sendMessage("§cSkyblock world not found!");
            return false;
        }
        $islandPosition = $this->calculateIslandPosition($world);
        $islandData = $this->dataManager->createIsland($player->getName(), $islandPosition);
        $this->placeIslandChest($islandPosition);
        $homePosition = new Position(
            $islandData["home"]["x"],
            $islandData["home"]["y"],
            $islandData["home"]["z"],
            $world
        );
        $player->teleport($homePosition);
        
        $player->sendMessage("§aIsland created successfully! Welcome to your new island!");
        return true;
    }

    private function calculateIslandPosition(World $world): Position {
        $islands = $this->dataManager->getAllIslands();
        $islandCount = count($islands);
        $spacing = 100;
        $gridSize = 10;
        
        $x = ($islandCount % $gridSize) * $spacing;
        $z = intval($islandCount / $gridSize) * $spacing;
        $y = 64;
        
        return new Position($x, $y, $z, $world);
    }

    private function placeIslandChest(Position $islandPosition): void {
        $chestTemplate = $this->dataManager->getChestTemplate();
        if ($chestTemplate["location"] === null) {
            return;
        }

        $chestLocation = $chestTemplate["location"];
        $chestPos = new Position(
            $islandPosition->getX() + $chestLocation["x"],
            $islandPosition->getY() + $chestLocation["y"],
            $islandPosition->getZ() + $chestLocation["z"],
            $islandPosition->getWorld()
        );
        $islandPosition->getWorld()->setBlock($chestPos, VanillaBlocks::CHEST());
        $tile = $islandPosition->getWorld()->getTile($chestPos);
        if ($tile instanceof Chest) {
            $inventory = $tile->getInventory();
            foreach ($chestTemplate["items"] as $itemData) {
                $item = $this->deserializeItem($itemData);
                if ($item !== null) {
                    $inventory->addItem($item);
                }
            }
        }
    }

    public function teleportToIsland(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($islandData["home"]["world"]);
        if ($world === null) {
            $player->sendMessage("§cIsland world not found!");
            return false;
        }

        $homePosition = new Position(
            $islandData["home"]["x"],
            $islandData["home"]["y"],
            $islandData["home"]["z"],
            $world
        );

        $player->teleport($homePosition);
        $player->sendMessage("§aWelcome back to your island!");
        return true;
    }

    public function setHome(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        $position = $player->getPosition();
        $islandData["home"] = [
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "world" => $position->getWorld()->getFolderName()
        ];

        $this->dataManager->updateIsland($player->getName(), $islandData);
        $player->sendMessage("§aHome location set!");
        return true;
    }

    public function inviteMember(Player $player, string $memberName): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] !== $player->getName()) {
            $player->sendMessage("§cOnly the island owner can invite members!");
            return false;
        }

        if (in_array($memberName, $islandData["members"])) {
            $player->sendMessage("§c$memberName is already a member of your island!");
            return false;
        }

        $islandData["members"][] = $memberName;
        $this->dataManager->updateIsland($player->getName(), $islandData);
        $player->sendMessage("§a$memberName has been invited to your island!");
        return true;
    }

    public function kickMember(Player $player, string $memberName): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] !== $player->getName()) {
            $player->sendMessage("§cOnly the island owner can kick members!");
            return false;
        }

        $memberIndex = array_search($memberName, $islandData["members"]);
        if ($memberIndex === false) {
            $player->sendMessage("§c$memberName is not a member of your island!");
            return false;
        }

        if ($memberName === $player->getName()) {
            $player->sendMessage("§cYou cannot kick yourself!");
            return false;
        }

        unset($islandData["members"][$memberIndex]);
        $islandData["members"] = array_values($islandData["members"]);
        
        $this->dataManager->updateIsland($player->getName(), $islandData);
        $player->sendMessage("§a$memberName has been kicked from your island!");
        return true;
    }

    public function leaveIsland(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] === $player->getName()) {
            $player->sendMessage("§cYou cannot leave your own island! Use /is delete instead.");
            return false;
        }

        $memberIndex = array_search($player->getName(), $islandData["members"]);
        if ($memberIndex !== false) {
            unset($islandData["members"][$memberIndex]);
            $islandData["members"] = array_values($islandData["members"]);
            $this->dataManager->updateIsland($islandData["owner"], $islandData);
        }

        $player->sendMessage("§aYou have left the island!");
        return true;
    }

    public function getMembers(Player $player): ?array {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return null;
        }

        return $islandData["members"];
    }

    public function deleteIsland(Player $player): bool {
        $islandData = $this->dataManager->getIsland($player->getName());
        if ($islandData === null) {
            $player->sendMessage("§cYou don't have an island!");
            return false;
        }

        if ($islandData["owner"] !== $player->getName()) {
            $player->sendMessage("§cOnly the island owner can delete the island!");
            return false;
        }

        $this->dataManager->deleteIsland($player->getName());
        $player->sendMessage("§aYour island has been deleted!");
        return true;
    }

    public function isOnIsland(Player $player, Position $position): bool {
        $allIslands = $this->dataManager->getAllIslands();
        
        foreach ($allIslands as $islandData) {
            if (!in_array($player->getName(), $islandData["members"])) {
                continue;
            }
            
            $islandPos = $islandData["position"];
            $distance = 50;
            
            if (abs($position->getX() - $islandPos["x"]) <= $distance &&
                abs($position->getZ() - $islandPos["z"]) <= $distance &&
                $position->getWorld()->getFolderName() === $islandPos["world"]) {
                return true;
            }
        }
        
        return false;
    }

    private function deserializeItem(array $data): ?Item {
        try {
            $item = ItemFactory::getInstance()->get($data["id"], $data["meta"] ?? 0, $data["count"] ?? 1);
            if (isset($data["nbt"])) {
                $item->setNamedTag(CompoundTag::create());
            }
            return $item;
        } catch (\Exception $e) {
            return null;
        }
    }
}