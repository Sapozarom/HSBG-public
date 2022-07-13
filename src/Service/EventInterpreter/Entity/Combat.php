<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Card;
use App\Service\EventInterpreter\Entity\Option;


class Combat extends Option
{
    private $id;

    private $ownerPlayerId;

    private $oppPlayerId ;

    private $oppName;

    private $ownerHealth;

    private $oppHealth;

    private $playerBoard = array();

    private $oppBoard = array();

    private $winner;

    private $damageArray = array();

    private $minions = array();


    public function setOppPlayerId($playerId)
    {
        $this->oppPlayerId = $playerId;
    }

    public function getOppPlayerId()
    {
        return $this->oppPlayerId;
    }

    public function setWinner($entityId)
    {
        $this->winner = $entityId;
    }

    public function getWinner()
    {
        return $this->winner;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwnerPlayerId($playerId)
    {
        $this->ownerPlayerId = $playerId;
    }

    public function getOwnerHealth()
    {
        return $this->ownerHealth;
    }

    public function setOwnerHealth($hp)
    {
        $this->ownerHealth = $hp;
    }

    public function getOppHealth()
    {
        return $this->oppHealth;
    }

    public function setOppHealth($hp)
    {
        $this->oppHealth = $hp;
    }

    public function getDamageArray()
    {
        return $this->damageArray;
    }

    public function addDamage($dmg)
    {
        array_push($this->damageArray,   $dmg);
    }

    public function getDamageById($id)
    {
        return $this->damageArray[$id];
    }

    public function addMinion(Card $minion)
    {
        $this->minions[$minion->getId()] = $minion;
        return $this;
    }

    public function addToPlayerBoard(Card $minion)
    {
        array_push($this->playerBoard, $minion);

        return $this;
    }

    public function getPlayerBoard()
    {
        \ksort($this->playerBoard);
        return $this->playerBoard;
    }

    public function addToOppBoard(Card $minion)
    {
        array_push($this->oppBoard, $minion);
        
        return $this;
    }

    public function getOppBoard()
    {
        \ksort($this->oppBoard);
        return $this->oppBoard;
    }

    public function setOppName($name)
    {
        $this->oppName = $name;
    }

    public function getOppName()
    {
        return $this->oppName;
    }

    
    public function checkBoard()
    {
        if($this->oppName === "Bartender" || $this->oppName === "Bob's"){
            $this->playerBoard = array();
            $this->oppBoard = array();
            
            foreach ($this->minions as $minion) {

                if( $minion->getPlayerId() == $this->ownerPlayerId){
                    
                    if(!isset($this->playerBoard[$minion->getPosition()]) )
                    {
                        $this->playerBoard[$minion->getPosition()] = $minion;
                    
                    } else {
                        if($minion->getId() < $this->playerBoard[$minion->getPosition()]->getId()){
                            $this->playerBoard[$minion->getPosition()] = $minion;
                            
                        }
                    }
                } elseif ($minion->getPlayerId() == $this->ownerPlayerId+8) {

                    if(!isset($this->oppBoard[$minion->getPosition()]))
                    {
                        $this->oppBoard[$minion->getPosition()] = $minion;
                    } else {
                        if($minion->getId() < $this->oppBoard[$minion->getPosition()]->getId()){
                            $this->oppBoard[$minion->getPosition()] = $minion;
                        }
                    }
                } 

                \ksort($this->playerBoard);
                \ksort($this->oppBoard);
            }
        }

        return $this;
    }

    public function getDamage()
    {
        return end($this->damageArray);
    }


    

}