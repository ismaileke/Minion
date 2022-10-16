<?php

namespace IsmailEke\Minion;

use IsmailEke\Minion\entity\MinionEntity;
use pocketmine\entity\Location;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class MinionListener implements Listener {

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onMinionPlace (PlayerInteractEvent $event) : void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $player->sendPopup("Block: " . $event->getBlock()->getIdInfo()->getBlockId() . ":" . $event->getBlock()->getMeta());
        $blockPos = $event->getBlock()->getPosition();
        if ($item->getId() == ItemIds::NETHER_STAR and $item->getNamedTag()->getTag("uniqueID") !== null) {
            $type = $item->getNamedTag()->getString("type");
            switch ($type) {
                case "miner":
                case "farmer":
                case "woodcutter":
                    $compoundTag = CompoundTag::create();
                    $compoundTag->setString("type", $type);
                    $compoundTag->setInt("level", $item->getNamedTag()->getInt("level"));
                    $compoundTag->setString("uniqueID", $item->getNamedTag()->getString("uniqueID"));
                    $compoundTag->setString("owner", $player->getName());
                    $minion = new MinionEntity(new Location(($blockPos->getX() + 0.5), ($blockPos->getY() + 1), ($blockPos->getZ() + 0.5), $player->getWorld(), 0.0, 0.0), $player->getSkin(), $compoundTag);
                    $minion->getInventory()->setItemInHand(($type === "miner" ? VanillaItems::IRON_PICKAXE() : ($type === "farmer" ? VanillaItems::IRON_HOE() : VanillaItems::IRON_AXE())));
                    $minion->setNameTag(TextFormat::BLUE . $player->getName() . "'s " . ucfirst($type) . " Minion");
                    $minion->setNameTagAlwaysVisible();
                    $minion->setScale(0.5);
                    $minion->spawnToAll();
                    $player->getInventory()->removeItem(VanillaItems::NETHER_STAR());
                    $player->sendMessage(TextFormat::GOLD . ucfirst($type) . TextFormat::GOLD . " Minion Spawned!");
                break;
            }
        }
    }
}