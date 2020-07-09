<?php

declare(strict_types=1);

namespace matze\football;

use pocketmine\block\Block;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;

class FootballEntity extends Human {

    /** @var int  */
    private $speedTicks = 0;

    /** @var int  */
    private $airTicks = 0;

    /** @var int  */
    private $waitTicks = 0;

    /**
     * @param Player $player
     */

    public function onCollideWithPlayer(Player $player) : void {
        if($player->isSprinting()){
            $this->setMotion(new Vector3($player->getDirectionVector()->x*2, 0.5, $player->getDirectionVector()->z*2));
            $this->speedTicks = 25;
        } elseif ($player->isSneaking()){
            $this->setMotion(new Vector3($player->getDirectionVector()->x/2, 0.5, $player->getDirectionVector()->z/2));
            $this->speedTicks = 5;
        } else {
            $this->setMotion(new Vector3($player->getDirectionVector()->x, 0.5, $player->getDirectionVector()->z));
            $this->speedTicks = 15;
        }
        $this->airTicks = 0;
        $this->waitTicks = 5;
        $this->setRotation($player->yaw, 0);
        $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
        //$this->getLevel()->broadcastLevelEvent($this, LevelEventPacket::EVENT_SOUND_DOOR_BUMP);
    }

    /**
     * @param int $currentTick
     * @return bool
     */

    public function onUpdate(int $currentTick) : bool {
        if($this->speedTicks > 0 && $this->waitTicks <= 0 && $this->isOnGround()){
            if(!$this->getFrontBlock()->isSolid()){
                $this->setMotion(new Vector3($this->getDirectionVector()->x/1.2, $this->airTicks / 24, $this->getDirectionVector()->z/1.2));
            } else {
                if($this->yaw + 180 >= 360){
                    $this->setRotation($this->yaw-180, 0);
                } else {
                    $this->setRotation($this->yaw+180, 0);
                }
                $this->setMotion(new Vector3($this->getDirectionVector()->x, 0, $this->getDirectionVector()->z));
            }
        }

        if(!$this->isOnGround()){
            $this->airTicks++;
        } else {
            $this->airTicks = 0;
        }
        if($this->speedTicks > 0){
            $this->speedTicks--;
        }
        if($this->waitTicks > 0){
            $this->waitTicks--;
        }

        /*if($this->getViewers() === []){
            $this->close();
            return false;
        }*/
        $this->setScale(1.5); //When the entity isn`t loaded, it turns back to it`s real scale
        return parent::onUpdate($currentTick);
    }

    /**
     * @param EntityDamageEvent $source
     */

    public function attack(EntityDamageEvent $source) : void {
        if($source instanceof EntityDamageByEntityEvent){
            $damager = $source->getDamager();
            if(!$damager instanceof Player){
                return;
            }
            $this->setMotion(new Vector3($damager->getDirectionVector()->x, 0.7, $damager->getDirectionVector()->z));
            $this->setRotation($damager->yaw, 0);
            $this->speedTicks = 25;
            $this->waitTicks = 5;
            $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
        }
        $source->setCancelled();
    }

    /**
     * @return Block
     */

    public function getFrontBlock() : Block {
        switch ($this->getDirection()){
            case 2:
                return $this->getLevel()->getBlock(new Vector3($this->x-1, $this->y, $this->z));
                break;
            case 0:
                return $this->getLevel()->getBlock(new Vector3($this->x+1, $this->y, $this->z));
                break;
            case 3:
                return $this->getLevel()->getBlock(new Vector3($this->x, $this->y, $this->z-1));
                break;
            case 1:
                return $this->getLevel()->getBlock(new Vector3($this->x, $this->y, $this->z+1));
                break;
        }
    }
}