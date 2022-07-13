<?php

namespace App\Service\LogCrawler\Entities;


class Entity
{
    private $id;

    private $name;

    private $tag;

    private $tagValue;

    private $pairArray = array();

    private $cardId;

    private $ownerPlayerId;

    private $zone;

    private $zonePosition;


    public function setId($id)
    {
        $this->id = $id;
        return $this;
    } 

    public function getId()
    {
        return $this->id;
    }

    public function setCardId($id)
    {
        $this->cardId = $id;
        return $this;
    } 

    public function getCardId()
    {
        return $this->cardId;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }  

    public function getName()
    {
        return $this->name;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }  

    public function getTag()
    {
        return $this->tag;
    }

    public function setTagValue($tagValue)
    {
        $this->tagValue = $tagValue;
        return $this;
    } 

    public function getTagValue()
    {
        return $this->tagValue;
    }

    public function setZone($zone)
    {
        $this->zone = $zone;
        return $this;
    }  

    public function getZone()
    {
        return $this->zone;
    }

    public function setZonePosition($position)
    {
        $this->zonePosition = $position;
        return $this;
    }  

    public function getZonePosition()
    {
        return $this->zonePosition;
    }

    public function getPairArray()
    {
        return $this->pairArray;
    }

    public function updatePairInArray()
    {
        $this->pairArray[$this->tag] = $this->tagValue;
        return $this;
    }

    public function updateTagInArray($tag, $tagValue)
    {
        $this->pairArray[$tag] = $tagValue;
        return $this;
    }

    public function getTagValueFromPairArray($tag)
    {
        if (isset($this->pairArray[$tag])) {
            return $this->pairArray[$tag];
        } else {
            return false;
        }
    }

    public function setOwnerPlayerId($playerId)
    {
        $this->ownerPlayerId = $playerId;
    }

    public function getOwnerPlayerId()
    {
        return $this->ownerPlayerId;
    }
}