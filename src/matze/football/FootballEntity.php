<?php

declare(strict_types=1);

namespace matze\football;

use pocketmine\block\Block;
use pocketmine\block\Water;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\MobSpawnParticle;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\sound\FizzSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\Server;

class FootballEntity extends Human {

    /** @var int  */
    private $waitTicks = 0;

    /** @var int  */
    private $airTicks = 0;

    /** @var float  */
    public $width = 0.01;
    /** @var float  */
    public $height = 0.01;

    /**
     * @param Player $player
     */

    public function onCollideWithPlayer(Player $player) : void {
        if($player->isSprinting()){
            $this->setMotion(new Vector3($player->getDirectionVector()->x*2, 0.5, $player->getDirectionVector()->z*2));
        } elseif ($player->isSneaking()){
            $this->setMotion(new Vector3($player->getDirectionVector()->x/2, 0.5, $player->getDirectionVector()->z/2));
        } else {
            $this->setMotion(new Vector3($player->getDirectionVector()->x, 0.5, $player->getDirectionVector()->z));
        }
        $this->waitTicks = 5;
        $this->setRotation($player->yaw, 0);
        $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
    }

    /**
     * @param int $currentTick
     * @return bool
     */

    public function onUpdate(int $currentTick) : bool {
        if($this->isClosed()) {
            return false;
        }

        if(!$this->isOnGround()) {
            $this->airTicks++;
        }

        if(Football::getFrontBlock($this)->isSolid()) {
            $yaw = $this->yaw - 180;
            if($yaw < 0) {
                $yaw += 360;
            }
            $this->setRotation($yaw, 0);
            $this->setMotion(new Vector3($this->getDirectionVector()->x * $this->motion->x, 0.5, $this->getDirectionVector()->z * $this->motion->z));

            $this->airTicks = 5;
        }

        $blockU = $this->getLevel()->getBlockAt((int) floor($this->x), (int) floor($this->y) - 1, (int) floor($this->z));
        if($blockU->isSolid() && $this->airTicks > 10) {
            $this->setMotion(new Vector3($this->motion->x * 1.1, $this->airTicks / 30, $this->motion->z * 1.1));
            $this->airTicks = 0;
        }

        if($this->level->getBlockAt((int) floor($this->x), (int) floor($this->y), (int) floor($this->z)) instanceof Water) {
            $this->setMotion(new Vector3($this->motion->x / 10, 0.16, $this->motion->z / 10));
        }

        if($this->isOnFire()) {
            $this->flagForDespawn();
            $this->getLevel()->addParticle(new HugeExplodeParticle($this));
        }

        $this->setScale(1.5);
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
            $this->waitTicks = 5;
            $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
        }
        $source->setCancelled();
    }

    /**
     * @return bool
     */

    public function canBePushed() : bool {
        return true;
    }
}