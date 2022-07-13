<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;


class Option
{
    private $blockId;

    private $optionNumber;

    private $mainEntity;

    private $targets = array();

    private $zone;

    private $zonePosition;

    private $playerId;

    public function setBlockId($blockId)
    {
        $this->blockId = $blockId;
    }

    public function getBlockId()
    {
        return $this->blockId;
    }

    public function setOptionNumber($optionNumber)
    {
        $this->optionNumber = $optionNumber;
    }

    public function getOptionNumber()
    {
        return $this->optionNumber;
    }

    public function setMainEntity($mainEntity)
    {
        $this->mainEntity = $mainEntity;
    }

    public function getMainEntity()
    {
        return $this->mainEntity;
    }

    public function addTarget(Option $target)
    {
        array_push($this->targets, $target);
        return $this;
    }

    public function getTargets()
    {
        return $this->targets;
    }

    public function findTargetById($id)
    {
        foreach ($this->targets as $target) {
            if ($target->getId() == $id)
            {
                return $target;
            }
        }
    }

    public function setZone($zone)
    {
        $this->zone = $zone;
    }

    public function getZone()
    {
        return $this->zone;
    }

    public function setZonePosition($zonePosition)
    {
        $this->zonePosition = $zonePosition;
    }

    public function getZonePosition()
    {
        return $this->zonePosition;
    }

    public function setPlayerId($playerId)
    {
        $this->playerId = $playerId;
    }

    public function getPlayerId()
    {
        return $this->playerId;
    }
    
}