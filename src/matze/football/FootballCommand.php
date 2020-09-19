<?php

namespace matze\football;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\Config;

class FootballCommand extends Command implements PluginIdentifiableCommand {

    /**
     * FootballCommand constructor.
     */

    public function __construct() {
        parent::__construct("football", "Football command", "/football <spawn | remove | addgate | removegate [ID]>");
        $this->setPermission("football.use");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
        if(!$sender instanceof Player){
            return;
        }
        if(!$this->testPermissionSilent($sender)){
            return;
        }
        if(!isset($args[0])){
            $sender->sendMessage($this->usageMessage);
            return;
        }
        switch ($args[0]){
            case "spawn": {
                if(!$sender->hasPermission("football.spawn")) return;
                Football::getInstance()->spawnFootball($sender);
                $sender->sendMessage("§7» §aYou have spawned a new football!");
                break;
            }
            case "remove": {
                if(!$sender->hasPermission("football.remove")) return;
                $count = 0;
                foreach (Server::getInstance()->getLevels() as $level){
                    foreach ($level->getEntities() as $entity){
                        if($entity instanceof FootballEntity){
                            if(!$entity->isClosed()){
                                $entity->close();
                                $count++;
                            }
                        }
                    }
                }
                $sender->sendMessage("§7» §aAll footballs were removed! §7(§aTotal§7:§a {$count}§7)");
                break;
            }
            case "addgate": {
                if(!$sender->hasPermission("football.addgate")) return;
                $name = $sender->getName();
                Football::$setup[$name]["Phase"] = Football::SETUP_POS1;

                $sender->sendMessage("§7» §aSet the first position of the gate.");
                break;
            }
            case "removegate": {
                if(!$sender->hasPermission("football.removegate")) return;
                $gateConfig = new Config(Football::getInstance()->getDataFolder()."gates.json", Config::JSON);
                if(!isset($args[1])) {
                    $sender->sendMessage($this->usageMessage);
                    return;
                }
                $id = $args[1];
                if(is_null($gateConfig->getNested("gates.{$id}"))) {
                    $sender->sendMessage("§7» §aThere is no gate with this ID!");
                    return;
                }
                $gateConfig->removeNested("gates.{$id}");
                $gateConfig->save();
                $sender->sendMessage("§7» §aGate with ID {$id} was successfully removed");
                break;
            }
            default: $sender->sendMessage($this->usageMessage);
        }
    }

    /**
     * @return Football
     */

    public function getPlugin() : Plugin {
        return Football::getInstance();
    }
}