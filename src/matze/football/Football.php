<?php

declare(strict_types=1);

namespace matze\football;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Football extends PluginBase {

    /** @var null  */
    private static $instance = null;

    public function onEnable() : void {
        self::$instance = $this;
        Football::getInstance()->getServer()->getCommandMap()->register("Football", new FootballCommand());
        Entity::registerEntity(FootballEntity::class, true);

        $this->saveResource("football.json");
        $this->saveResource("football.png");
    }

    /**
     * @return static
     */

    public static function getInstance() : self {
        return self::$instance;
    }

    /**
     * @param Player $player
     */

    public function spawnFootball(Player $player) : void {
        $nbt = Entity::createBaseNBT($player);
        $nbt->setTag($player->namedtag->getTag("Skin"));

        $footballEntity = new FootballEntity($player->getLevel(), $nbt);

        $image = imagecreatefrompng($this->getDataFolder()."football.png");
        $bytes = "";
        $l = (int) @getimagesize($this->getDataFolder()."football.png")[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($image, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($image);

        $footballEntity->setSkin(new Skin("Football", $bytes, "", "geometry.football", file_get_contents($this->getDataFolder()."football.json")));
        $footballEntity->setScale(1.5);
        $footballEntity->sendSkin();
        $footballEntity->spawnToAll();
    }

    /**
     * @param Entity $entity
     * @return Block
     */

    public static function getFrontBlock(Entity $entity) : Block {
        switch ($entity->getDirection()){
            case 2: return $entity->getLevel()->getBlock(new Vector3($entity->x-1, $entity->y, $entity->z));
            case 0: return $entity->getLevel()->getBlock(new Vector3($entity->x+1, $entity->y, $entity->z));
            case 3: return $entity->getLevel()->getBlock(new Vector3($entity->x, $entity->y, $entity->z-1));
            case 1: return $entity->getLevel()->getBlock(new Vector3($entity->x, $entity->y, $entity->z+1));
            default: return $entity->getLevel()->getBlock(new Vector3($entity->x, $entity->y, $entity->z));
        }
    }
}

