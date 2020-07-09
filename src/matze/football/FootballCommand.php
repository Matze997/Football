<?php

namespace matze\football;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class FootballCommand extends Command {

    /**
     * FootballCommand constructor.
     */

    public function __construct() {
        parent::__construct("football", "Spawn football");
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
        Football::getInstance()->spawnFootball($sender);
    }
}