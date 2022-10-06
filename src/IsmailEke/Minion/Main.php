<?php

namespace IsmailEke\Minion;

use IsmailEke\Minion\command\MinionCommand;
use IsmailEke\Minion\entity\MinionEntity;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class Main extends PluginBase {

    /**
     * @return void
     */
    public function onEnable () : void {
        $this->getLogger()->info("Minion Plugin Online");
        $this->getServer()->getCommandMap()->register("minion", new MinionCommand());
        $this->getServer()->getPluginManager()->registerEvents(new MinionListener(), $this);
        EntityFactory::getInstance()->register(MinionEntity::class, function (World $world, CompoundTag $nbt) : MinionEntity {
            return new MinionEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["MinionEntity"]);
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
    }

    /**
     * @return void
     */
    public function onDisable () : void {
        $this->getLogger()->info("Minion Plugin Offline");
    }
}