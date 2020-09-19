<?php

namespace matze\football;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;
use pocketmine\utils\Config;

class EventListener implements Listener {

    /**
     * EventListener constructor.
     */

    public function __construct() {
        Server::getInstance()->getPluginManager()->registerEvents($this, Football::getInstance());
    }

    /**
     * @param BlockBreakEvent $event
     */

    public function onBreak(BlockBreakEvent $event) : void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();

        $x = $block->x;
        $y = $block->y;
        $z = $block->z;

        $setup = Football::$setup;
        if(!isset($setup[$name])) return;
        $setup = Football::$setup[$name];
        $event->setCancelled();
        $save = true;
        switch ($setup["Phase"]) {
            case Football::SETUP_POS1: {
                $player->sendMessage("§7» §aFirst position set to X={$x}, Y={$y}, Z={$z}.\n\n§7» §aNow set the second gate position.");
                $setup["Pos1"] = "{$x},{$y},{$z}";

                $setup["Phase"] = Football::SETUP_POS2;
                break;
            }
            case Football::SETUP_POS2: {
                $player->sendMessage("§7» §aSecond position set to X={$x}, Y={$y}, Z={$z}.\n\n§7» §aNow set the Football respawn position.");
                $setup["Pos2"] = "{$x},{$y},{$z}";

                $setup["Phase"] = Football::SETUP_FOOTBALL_POS;
                break;
            }
            case Football::SETUP_FOOTBALL_POS: {
                $gateConfig = new Config(Football::getInstance()->getDataFolder()."gates.json", Config::JSON, ["gates" => []]);
                $id = 1;
                while (!is_null($gateConfig->getNested("gates.$id"))) {
                    $id++;
                }
                $player->sendMessage("§7» §aFootball position set to X={$x}, Y={$y}, Z={$z}.\n\n§7» §aSetup finished! gate was saved under the ID §7{$id}");
                $setup["BallPos"] = "{$x},{$y},{$z}";

                $gateConfig->setNested("gates.{$id}", [
                    "Pos1" => $setup["Pos1"],
                    "Pos2" => $setup["Pos2"],
                    "BallPos" => $setup["BallPos"]
                ]);
                $gateConfig->save();

                $save = false;
                break;
            }
        }
        if($save) {
            Football::$setup[$name] = $setup;
            return;
        }
        $setup = Football::$setup;
        unset($setup[$name]);
        Football::$setup = $setup;
    }

    /**
     * @param PlayerQuitEvent $event
     */

    public function onQuit(PlayerQuitEvent $event) : void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $setup = Football::$setup;

        if(!isset($setup[$name])) return;
        unset($setup[$name]);
        Football::$setup = $setup;
    }
}