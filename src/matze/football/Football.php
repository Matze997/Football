<?php

declare(strict_types=1);

namespace matze\football;

use matze\football\command\FootballCommand;
use matze\football\entity\FootballEntity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\World;

class Football extends PluginBase {
    private static Football $instance;

    public static function getInstance(): Football{
        return self::$instance;
    }

    public function onEnable(): void{
        self::$instance = $this;
        $this->saveResource("football.json");
        $this->saveResource("football.png");
        Server::getInstance()->getCommandMap()->register("football", new FootballCommand());
        EntityFactory::getInstance()->register(FootballEntity::class, function(World $world, CompoundTag $nbt) : FootballEntity{
            return new FootballEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["Football"]);
    }
}