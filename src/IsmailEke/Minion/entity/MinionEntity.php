<?php

namespace IsmailEke\Minion\entity;

use IsmailEke\Minion\inventory\MinionInventory;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;

class MinionEntity extends Human {

    /** @var array */
    public array $availableBlocks = [
        "miner" => [
            "1:0",
            "2:0"
        ],
        "farmer" => [
            "59:3",
            "59:4",
            "59:7",
            "59:2"
        ],
        "woodcutter" => []
    ];

    /** @var array */
    public array $levelPrices = [
        "miner" => [
            2 => 5000,
            3 => 10000
        ],
        "farmer" => [
            2 => 5000,
            3 => 10000
        ],
        "woodcutter" => [
            2 => 5000,
            3 => 10000
        ]
    ];

    /** @var CompoundTag */
    public CompoundTag $minionNBT;

    /**
     * @param CompoundTag $nbt
     * @return void
     */
    public function initEntity (CompoundTag $nbt): void {
        $this->setScale(0.5);
        $this->setNameTagAlwaysVisible();
        $this->setRotation(0.0, 0.0);
        parent::initEntity($nbt);
        $this->minionNBT = $nbt;
    }

    /**
     * @param EntityDamageEvent $source
     * @return void
     */
    public function attack (EntityDamageEvent $source) : void {
        if ($source instanceof EntityDamageByEntityEvent) {
            if (!$source->getDamager() instanceof Player) return;
            if ($this->saveNBT()->getString("owner") !== $source->getDamager()->getName()) return;
            $minionGUI = new MinionInventory($this);
            $minionGUI->mainInv($source->getDamager());
        }
        $source->cancel();
    }

    /**
     * @return CompoundTag
     */
    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("type", $this->minionNBT->getString("type"));
        $nbt->setInt("level", $this->minionNBT->getInt("level"));
        $nbt->setString("uniqueID", $this->minionNBT->getString("uniqueID"));
        $nbt->setString("owner", $this->minionNBT->getString("owner"));
        return $nbt;
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick (int $tickDiff = 1) : bool {
        if ($this->isOnFire()) {
            $this->setOnFire(0);
        }
        if ($this->saveNBT()->getTag("type") === null) return false;
        $tick = $this->saveNBT()->getInt("level") * 50;
        if ($this->ticksLived % $tick == 0) {
            switch ($this->saveNBT()->getString("type")) {
                case "miner":
                case "farmer":
                case "woodcutter":
                    $result = false;
                    foreach ($this->availableBlocks[$this->saveNBT()->getString("type")] as $block) {
                        if (($this->getInventory()->canAddItem(StringToItemParser::getInstance()->parse(LegacyBlockIdToStringIdMap::getInstance()->legacyToString(explode(":", $block)[0]))))) {
                            $result = true;
                            break;
                        }
                    }
                    if ($result) {
                        $block = $this->getBlock();
                        while (true) {
                            if (!in_array($block->getId() . ":" . $block->getMeta(), $this->availableBlocks[$this->saveNBT()->getString("type")])) {
                                $block = $this->getBlock();
                                continue;
                            }
                            break;
                        }
                        $this->lookAt($block->getPosition()->asVector3());
                        $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
                        $this->getInventory()->addItem($block->asItem());
                        $this->getWorld()->addParticle($block->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
                        $this->getWorld()->setBlockAt($block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ(), VanillaBlocks::AIR());
                    } else {
                        $this->setNameTag($this->getNameTag() . "\n" . TextFormat::RED . "Inventory Is Full!");
                    }
                break;
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    /**
     * @return Block
     */
    public function getBlock () : Block {
        return $this->getWorld()->getBlockAt(mt_rand(min(($this->location->getX() + 3), ($this->location->getX() - 3)), max(($this->location->getX() + 3), ($this->location->getX() - 3))), ($this->saveNBT()->getString("type") === "miner" ? $this->location->getY() - 1 : $this->location->getY()), mt_rand(min(($this->location->getZ() + 3), ($this->location->getZ() - 3)), max(($this->location->getZ() + 3), ($this->location->getZ() - 3))));
    }
}