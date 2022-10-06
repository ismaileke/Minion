<?php

namespace IsmailEke\Minion\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MinionCommand extends Command {

    public function __construct () {
        parent::__construct("minion", "Minion Command", "/minion");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute (CommandSender $sender, string $commandLabel, array $args) {
        if (!$sender instanceof Player) return;
        if (!Server::getInstance()->isOp($sender->getName())) return;
        if (count($args) > 0) {
            /**
             * @param Player $dest
             * @return void
             */
            $giveItem = function (Player $dest) use ($args) : void {
                switch (strtolower($args[0])) {
                    case "farmer":
                    case "miner":
                    case "woodcutter":
                        $compoundTag = CompoundTag::create();
                        $compoundTag->setString("type", strtolower($args[0]));
                        $compoundTag->setInt("level", 1);
                        $compoundTag->setString("uniqueID", substr(str_shuffle("qwertyuiopasdfghjklzxcvbnmx"),0, 10));
                        $dest->getInventory()->addItem(VanillaItems::NETHER_STAR()->setNamedTag($compoundTag)->setCustomName(TextFormat::AQUA . ucfirst($args[0]) . " Minion")->setLore([TextFormat::GOLD . $dest->getName()]));
                        $dest->sendMessage(TextFormat::GOLD . "Added minion to your inventory");
                    break;
                    default:
                        $dest->sendMessage("Usage: /minion <miner-woodcutter-farmer>");
                    break;
                }
            };
            if (count($args) == 1) {
                $giveItem($sender);
            } else {
                unset($args[0]);
                $dest = Server::getInstance()->getPlayerByPrefix(implode(" ", $args));
                if ($dest instanceof Player) {
                    $giveItem($dest);
                    $sender->sendMessage(TextFormat::GOLD . $dest->getName() . TextFormat::GOLD . " player given a minion");
                } else {
                    $sender->sendMessage(TextFormat::RED . implode(" ", $args) . " not found.");
                }
            }
        } else {
            $sender->sendMessage("Usage: /minion <miner-woodcutter-farmer> <playerName=null>");
        }
    }
}