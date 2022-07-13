<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Combat;
use App\Entity\Event;


class Round 
{
    private $roundNumber;

    private $events = array();

    //game events and opponent events
    private $nextOpponent;

    //player actions
    private $actions = array();

    private $leaderboard;

    private $minions = array();

    private $optionsArray = array();

    private $combat = null;

    private $mulliganNext = false;
    
    private $mulliganKey = null;

    
    public function getCombat()
    {
        return $this->combat;
    }

    public function setCombat($combat)
    {
        if($this->combat == null)
        {
            $this->combat = $combat;
        }
    }

    public function setRoundNumber($number)
    {
        $this->roundNumber = $number;
    }
    
    public function getRoundNumber()
    {
        return $this->roundNumber; 
    }

    public function setNextOpponent($player)
    {
        $this->nextOpponent = $player;
    }

    public function getNextOpponent()
    {
        return $this->nextOpponent;
    }

    public function addToLeaderBoard($player, $value)
    {
        $this->leaderboard[$player] = $value;
    }

    public function copyLeaderboard($leaderboard)
    {
        $this->leaderboard = $leaderboard;
    }

    public function getLeaderboard()
    {
        return $this->leaderboard;
    }

    public function addAction($event)
    {   
        if ($this->mulliganNext == true
        && $this->mulliganKey != null
        && $event->getType() == 'BOARD' 
        && $event->getTarget() != null
        && ($event->getTarget()->getType() == 'SPELL'
            || $event->getTarget()->getType() == 'HERO_POWER')
        ) {
            $tempEvent = clone ($this->events[$this->mulliganKey]);

            unset($this->events[$this->mulliganKey]);

            array_push($this->events, $event);
            array_push($this->events, $tempEvent);

            $this->mulliganNext = false;
            $this->mulliganKey = null;
        } else {
            array_push($this->events, $event);
        }
       
        if ($event->getType() == 'MULLIGAN') {
            $this->mulliganNext = true;
            $this->mulliganKey = array_key_last($this->events);
        }
    }

    public function addEvent($event)
    {
        array_push($this->events, $event);
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function addZeroEvents($array)
    {
        array_unshift($this->events, $array);
    }

    public function addMinion($entity)
    {
        if (!isset($this->minions[$entity->getId()])) {
            $this->minions[$entity->getId()] = $entity;
        } 
    }

    public function findMinion($id)
    {
        if(isset($this->minions[$id])) {
            return true;
        }
        
        return false;
    }

    public function addOptionBlock($optionBlock)
    {
        array_push($this->optionsArray, $optionBlock);
    }

    public function countOptions()
    {
        return count($this->optionsArray);
    }

}