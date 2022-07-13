<?php

namespace App\Service\EventInterpreter\Entity;

class HeroPower
{
    private $id;

    private $cardId;

    private $name;

    private $cost = null;

    private $activated = false;

    private $modifier;

    private $playerId;

    private $dbfId;

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

    public function setDbId($dbId)
    {
        $this->dbId = $dbId;
    }

    public function getDbId()
    {
        return $this->dbId;
    }

    public function setCost($cost)
    {
        $this->cost = $cost;
    }

    public function getCost()
    {
        return $this->cost;
    }

    public function setActivated($bool)
    {
        $this->activated = $bool;
    }

    public function getActivated()
    {
        return $this->activated;
    }

    public function setModifier($m)
    {
        $this->modifier = $m;
    }

    public function getModifier()
    {
        return $this->modifier;
    }
}
