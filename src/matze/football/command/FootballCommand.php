<?php

declare(strict_types=1);

namespace matze\football\command;

use JsonException;
use matze\football\entity\FootballEntity;
use matze\football\Football;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

class FootballCommand extends Command implements PluginOwned {
    public function __construct(){
        parent::__construct("football", "Football Command", "/football <spawn | remove>", ["fb"]);
        $this->setPermission("football.use");
    }

    /**
     * @throws JsonException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if(!$sender instanceof Player){
            $sender->sendMessage("§7» §cYou can only use this command ingame!");
            return;
        }
        if(!$this->testPermission($sender)) {
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage($this->getUsage());
            return;
        }
        switch($args[0]) {
            case "spawn": {
                if(!$this->testPermission($sender, "football.spawn")) {
                    break;
                }
                if (!isset($args[1])) {
                    FootballEntity::spawn($sender->getLocation());
                } else {
                    for ($count = 1; $count <= (int)($args[1]); $count++) {
                        FootballEntity::spawn($sender->getLocation());
                    }
                }
                $sender->sendMessage("§7» §aYou have spawned a new football!");
                break;
            }
            case "remove": {
                if(!$this->testPermission($sender, "football.remove")) {
                    break;
                }
                $count = 0;
                $world = $sender->getWorld();
                foreach ($world->getEntities() as $entity){
                    if(($entity instanceof FootballEntity) && !$entity->isClosed()) {
                        $entity->flagForDespawn();
                        $count++;
                    }
                }
                $sender->sendMessage("§7» §aSuccessfully removed §6" . $count . "§a footballs.");
                break;
            }
            default: {
                $sender->sendMessage($this->getUsage());
            }
        }
    }

    public function getOwningPlugin(): Plugin{
        return Football::getInstance();
    }
}