<?php

namespace App\Service\EventInterpreter;

use App\Service\EventInterpreter\Entity\Event;
use App\Service\EventInterpreter\Entity\CombatEvent;


/**
 * Class that store all events aquired from parsing log file. As event we understand every significant data change during a gameplay.
 */
class GameHistoryService
{
    private $eventId = 1;
    private $eventArray = array();


    /**
     * Create new event. Universal function tha handles all types of event apart from COMBAT type
     *
     * @param  $type |event type. One from GAME, BOARD, MULLIGAN
     * @param  $timestamp | timestamp of event
     * @param  $text | event message
     * @param  $playerBoard | array of player cards
     * @param  $secondBoard | array of bartender cards
     * @param  $hand | players hand
     * @param  $playerGold | players gold
     * @param  $tavernTier | current tavern tier
     * @param  $rerollCost | cost of refreshing shop
     * @param  $tavernUpgradeCost | cost of upgrading tavern tier
     * @param  $heroPowerUsed | bool value 
     * @param  $target | target of event
     * @param  $playerHealth | current players health
     * @return void
     */
    public function historyEvent($type = 'GAME', $timestamp, $text, $playerBoard = null, $secondBoard = null, $hand = null,  $playerGold = null, $tavernTier = 1 , $rerollCost = null, $tavernUpgradeCost = null, $heroPowerUsed = false, $target = null, $playerHealth = null )
    {
        $event = new Event();

        $event->setTimestamp($timestamp);
        $event->setType($type);
        $event->setContent($text);
        $event->setPlayerGold($playerGold);
        $event->setTavernTier($tavernTier);
        $event->setRerollCost($rerollCost);
        $event->setUpgradeCost($tavernUpgradeCost);
        $event->setHeroPowerUsed($heroPowerUsed);
        $event->setPlayerHealth($playerHealth);
        $event->setCardCollections($playerBoard, $secondBoard, $hand);
        $event->setTarget($target);    
        
        $this->eventId++;
        return $event;
    }

    /**
     * Function that handles combat event at the end of every round
     *
     * @param  $timestamp |timestamp of event
     * @param  $text | event message
     * @param  $playerBoard | players board
     * @param  $secondBoard | opponent board
     * @param  $hand | players hand
     * @param  $winner | winer of fight (-1 = opp wins, 0 = draw, 1 = player wins)
     * @param  $damage | damage dealt to loser
     * @param  $opponent | opponents hero
     * @param  $oppHealth | ammount of opponents health
     * @param  $playerHealth | player hero
     * @param  $damageArray | array of damage dealt
     * @return void
     */
    public function combatEvent($timestamp, $text, $playerBoard = null, $secondBoard = null, $hand = null, $winner, $damage, $opponent, $oppHealth, $playerHealth, $damageArray)
    {
        $event = new CombatEvent;

        $event->setTimestamp($timestamp);
        $event->setContent($text);
        $event->setWinner($winner);
        $event->setDamageArray($damage);
        $event->setOpponent($opponent);
        $event->setOpponentHealth($oppHealth);
        $event->setPlayerHealth($playerHealth);      
        $event->setCardCollections($playerBoard, $secondBoard, $hand);
                
        $this->eventId++;
        return $event;
    }

    /**
     * @return Returns full array of all events
     */
    public function getEventArray()
    {
        return $this->eventArray;
    }
}