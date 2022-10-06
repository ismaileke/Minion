<?php

namespace IsmailEke\Minion\inventory;

use IsmailEke\Minion\entity\MinionEntity;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\DyeColorIdMap;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MinionInventory {

    /** @var MinionEntity */
    public MinionEntity $entity;

    /**
     * @param MinionEntity $entity
     */
    public function __construct (MinionEntity $entity) {
        $this->entity = $entity;
    }

    public function mainInv (Player $player) : void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName($this->entity->saveNBT()->getString("owner") . "'s " . ucfirst($this->entity->saveNBT()->getString("type")) . " Minion");
        for ($i = 0; $i < 27; $i++) {
            $menu->getInventory()->setItem($i, VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColorIdMap::getInstance()->fromId(mt_rand(0, 9)))->asItem());
        }
        $menu->getInventory()->setItem(18, VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::YELLOW . "Accumulated Items"));
        $menu->getInventory()->setItem(22, VanillaItems::EXPERIENCE_BOTTLE()->setCustomName(TextFormat::GREEN . "Level Up Your Minion")->setLore([TextFormat::GRAY ."Minion Level: " . TextFormat::GOLD . $this->entity->saveNBT()->getInt("level")]));
        $menu->getInventory()->setItem(26, VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem()->setCustomName(TextFormat::RED . "Remove Minion"));
        $menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            switch ($transaction->getItemClicked()->getId()) {
                case ItemIds::CHEST:
                    $player->getNetworkSession()->sendDataPacket(LevelSoundEventPacket::create(LevelSoundEvent::LEVELUP, $player->getPosition()->asVector3(), -1, ":", false, false));
                    $this->minionInv($player);
                break;
                case ItemIds::EXPERIENCE_BOTTLE:
                    $player->getNetworkSession()->sendDataPacket(LevelSoundEventPacket::create(LevelSoundEvent::LEVELUP, $player->getPosition()->asVector3(), -1, ":", false, false));
                    $player->removeCurrentWindow();
                    if ($this->entity->saveNBT()->getInt("level") < 3) {
                        $price = $this->entity->levelPrices[$this->entity->saveNBT()->getString("type")][$this->entity->saveNBT()->getInt("level") + 1];
                        if (EconomyAPI::getInstance()->myMoney($player) >= $price) {
                            EconomyAPI::getInstance()->reduceMoney($player, $price);
                            $this->entity->minionNBT->setInt("level", $this->entity->saveNBT()->getInt("level") + 1);
                            $player->sendMessage(TextFormat::GREEN . "The minion's level has been increased to " . $this->entity->saveNBT()->getInt("level") . ".");
                        } else {
                            $player->sendMessage(TextFormat::RED . "You don't have enough money.");
                        }
                    } else {
                        $player->sendMessage(TextFormat::YELLOW . "It's the last level already.");
                    }
                break;
                case ItemIds::WOOL:
                    $player->getNetworkSession()->sendDataPacket(LevelSoundEventPacket::create(LevelSoundEvent::LEVELUP, $player->getPosition()->asVector3(), -1, ":", false, false));
                    $player->removeCurrentWindow();
                    $player->getWorld()->dropItem($this->entity->getPosition()->asVector3(), VanillaItems::NETHER_STAR()->setNamedTag($this->entity->saveNBT())->setCustomName(TextFormat::AQUA . ucfirst($this->entity->saveNBT()->getString("type")) . " Minion")->setLore([TextFormat::GOLD . $player->getName()]));
                    $this->entity->close();
                break;
            }
            return $transaction->discard();
        });
        $menu->send($player);
    }

    public function minionInv (Player $player) : void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName($this->entity->saveNBT()->getString("owner") . "'s " . ucfirst($this->entity->saveNBT()->getString("type")) . " Inventory");
        $menu->getInventory()->setContents($this->entity->getInventory()->getContents());
        $menu->getInventory()->setItem(0, VanillaBlocks::CHEST()->asItem()->setCustomName(TextFormat::GREEN . "Collect all items"));
        $menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            if ($transaction->getItemClicked()->getId() == ItemIds::CHEST) {
                $player->getNetworkSession()->sendDataPacket(LevelSoundEventPacket::create(LevelSoundEvent::LEVELUP, $player->getPosition()->asVector3(), -1, ":", false, false));
                $player->removeCurrentWindow();
                if (count($this->entity->getInventory()->getContents()) < 2) return $transaction->discard();
                foreach ($this->entity->getInventory()->getContents() as $index => $item) {
                    if ($index != 0) {
                        if ($player->getInventory()->canAddItem($item)) {
                            $player->getInventory()->addItem($item);
                            $this->entity->getInventory()->setItem($index, VanillaItems::AIR());
                        } else {
                            $player->sendMessage(TextFormat::RED . "Your inventory is full!");
                            break;
                        }
                    }
                }
                $player->sendMessage(TextFormat::GREEN . "Collected items from minion");
                $player->getNetworkSession()->sendDataPacket(LevelSoundEventPacket::create(LevelSoundEvent::CHARGE, $player->getPosition()->asVector3(), -1, ":", false, false));
            }
            return $transaction->discard();
        });
        $menu->send($player);
    }
}

