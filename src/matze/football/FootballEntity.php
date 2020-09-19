<?php

declare(strict_types=1);

namespace matze\football;

use pocketmine\block\Water;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\utils\Config;

class FootballEntity extends Human {

    /** @var int  */
    private $airTicks = 0;

    /** @var float  */
    public $width = 0.4;
    /** @var float  */
    public $height = 0.4;

    /**
     * @param int $currentTick
     * @return bool
     */

    public function onUpdate(int $currentTick) : bool {
        if($this->isClosed()) {
            return false;
        }

        $gateId = $this->getgate();
        if(!is_null($gateId)) {
            $gateConfig = new Config(Football::getInstance()->getDataFolder()."gates.json", Config::JSON, ["gates" => []]);

            $ballPos = Football::stringVectorToVector3($gateConfig->getNested("gates.{$gateId}.BallPos"));

            $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BLAST);
            for ($n = 0; $n <= 10; $n++) $this->getLevel()->addParticle(new DustParticle($this->add(mt_rand(-9, 9) / 10, mt_rand(-9, 9) / 10, mt_rand(-9, 9) / 10), mt_rand(), mt_rand(), mt_rand()));

            $this->teleport($ballPos);
            return parent::onUpdate($currentTick);
        }

        foreach($this->getLevel()->getNearbyEntities($this->boundingBox->expandedCopy(0.2, 0.2, 0.2), $this) as $player){
            if($player instanceof Player) {
                $this->setRotation($player->yaw, 0);
                if($player->isSprinting()){
                    $this->setMotion(new Vector3($player->getDirectionVector()->x*2, 0.5, $player->getDirectionVector()->z*2));
                } elseif ($player->isSneaking()){
                    $this->setMotion(new Vector3($player->getDirectionVector()->x/2, 0.5, $player->getDirectionVector()->z/2));
                } else {
                    $this->setMotion(new Vector3($player->getDirectionVector()->x, 0.5, $player->getDirectionVector()->z));
                }
                $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
            }
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
            $pk = new PlaySoundPacket();
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->soundName = "random.explode";
            $pk->volume = 1;
            $pk->pitch = 1;
            $this->getLevel()->broadcastGlobalPacket($pk);
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
            $this->setRotation($damager->yaw, 0);
            $this->setMotion(new Vector3($damager->getDirectionVector()->x, 0.7, $damager->getDirectionVector()->z));
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

    /**
     * @return int|null
     */

    public function getGate() : ?int {
        $gateConfig = new Config(Football::getInstance()->getDataFolder()."gates.json", Config::JSON, ["gates" => []]);

        foreach ($gateConfig->get("gates") as $id => $data) {
            $pos1 = Football::stringVectorToVector3($data["Pos1"]);
            $pos2 = Football::stringVectorToVector3($data["Pos2"]);

            if(
                $this->x >= min($pos1->x, $pos2->x) && $this->x <= max($pos1->x, $pos2->x) + 1 &&
                $this->y >= min($pos1->y, $pos2->y) - 0.2 && $this->y <= max($pos1->y, $pos2->y) + 1 &&
                $this->z >= min($pos1->z, $pos2->z) && $this->z <= max($pos1->z, $pos2->z) + 1
            ){
                return $id;
            }
        }
        return null;
    }
}