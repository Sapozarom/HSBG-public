<?php

namespace App\Service\EventInterpreter\Entity;

class Card
{
    private $id;

    private $cardId;

    private $name;

    private $zone;

    private $position;

    private $type;

    private $health;

    private $attack;

    private $playerId;

    private $dbId;

    //DISPLAY AND AFIXES

    private $tripleCheck = false; 

    private $golden = false; 

    private $deathrattle = false; 

    private $reborn = false;  

    private $trigger = false;

    private $poisonous = false;

    private $legendary = false; 

    private $taunt = false; 

    private $frozen = false; 

    private $divineShield = false; 

    private $techLvl;

    private $tribe;

    
    public function setTribe($tribe)
    {
        $this->tribe = $tribe;
    }

    public function getTribe()
    {
        return $this->tribe;
    }


    public function getDeathrattle()
    {
        return $this->deathrattle;
    }

    public function isDeathrattle($bool)
    {
        $this->deathrattle = $bool;
    }

    public function getReborn()
    {
        return $this->reborn;
    }

    public function isReborn($bool)
    {
        $this->reborn = $bool;
    }

    public function getTrigger()
    {
        return $this->trigger;
    }

    public function isTrigger($bool)
    {
        $this->trigger = $bool;
    }

    public function getPoisonous()
    {
        return $this->poisonous;
    }

    public function isPoisonous($bool)
    {
        $this->poisonous = $bool;
    }

    public function getLegendary()
    {
        return $this->legendary;
    }

    public function isLegendary($bool)
    {
        $this->legendary = $bool;
    }

    public function getTaunt()
    {
        return $this->taunt;
    }

    public function isTaunt($bool)
    {
        $this->taunt = $bool;
    }

    public function getFrozen()
    {
        return $this->frozen;
    }

    public function isFrozen($bool)
    {
        $this->frozen = $bool;
    }

    public function getDivineShield()
    {
        return $this->divineShield;
    }

    public function isDivineShield($bool)
    {
        $this->divineShield = $bool;
    }

    public function setTechLvl($lvl)
    {
        $this->techLvl = $lvl;
    }

    public function getTechLvl()
    {
        return $this->techLvl;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
    }

    public function getCardId()
    {
        return $this->cardId;
    }

    public function setPlayerId($playerId)
    {
        $this->playerId = $playerId;
    }

    public function getPlayerId()
    {
        return $this->playerId;
    }

    public function setName($name)
    {
        if ($name != 'FULL_ENTITY') {
            $this->name = $name;
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setZone($zone)
    {
        $this->zone = $zone;
    }

    public function getZone()
    {
        return $this->zone;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setHealth($health)
    {
        $this->health = $health;
    }

    public function getHealth()
    {
        return $this->health;
    }

    public function setAttack($attack)
    {
        $this->attack = $attack;
    }

    public function getAttack()
    {
        return $this->attack;
    }

    public function setGolden($bool)
    {
        $this->golden = $bool;
    }

    public function getGolden()
    {
        return $this->golden;
    }
    
    public function setTripleCheck($bool)
    {
        $this->tripleCheck = $bool;
    }

    public function getTripleCheck()
    {
        return $this->tripleCheck;
    }

    public function setDbId($dbId)
    {
        $this->dbId = $dbId;
    }

    public function getDbId()
    {
        return $this->dbId;
    }
}
