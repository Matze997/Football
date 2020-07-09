<?php

namespace matze\football;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class FootballCommand extends Command {

    /**
     * FootballCommand constructor.
     */

    public function __construct() {
        parent::__construct("football", "Spawn football", "/football <spawn|remove>");
        $this->setPermission("football.spawn");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
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
            case "spawn":
                Football::getInstance()->spawnFootball($sender);
                $sender->sendMessage("§7» §aYou have spawned a new football!");
                break;
            case "remove":
                foreach (Server::getInstance()->getLevels() as $level){
                    foreach ($level->getEntities() as $entity){
                        if($entity instanceof FootballEntity){
                            if(!$entity->isClosed()){
                                $entity->close();
                            }
                        }
                    }
                }
                $sender->sendMessage("§7» §aAll footballs were removed!");
                break;
            default:
                $sender->sendMessage($this->usageMessage);
        }
    }
}