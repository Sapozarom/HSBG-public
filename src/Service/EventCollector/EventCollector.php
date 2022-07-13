<?php

namespace App\Service\EventCollector;

use App\Service\EventCollector\Entities\Event;
use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Option;
use App\Service\EventInterpreter\Entity\OptionBlock;

/**
 * Service that takes data from Crawler and creates event that will be next pass to EventInterpreter
 */
class EventCollector
{   
    /**
     * Array in which all Events are contained 
     *
     * @var array
     */
    private $eventContainer = array();

    
    public function __construct()
    {
        $this->eventContainer = array();       
    }

    /**
     * Adds event to array 
     *
     * @param Event $event
     * @return void
     */
    public function addEventToContainer(Event $event)
    {
        array_push($this->eventContainer, $event);
    }

    /**
     * Returns array with events
     *
     * @return array()
     */
    public function getEventContainer()
    {
        return $this->eventContainer;
    }

    /**
     * Creates event where new entity is created in logs
     *
     * @param $timestamp
     * @param $entityId
     * @param string|null $entityName
     * @param string|null $cardId
     * @return void
     */
    public function createEntity($timestamp, $entityId, ?string $entityName, ?string $cardId)
    {
        $newEvent = new Event();
        $newEvent->setType('CR_EN');
        $content = 'id='. $entityId;

        if ($entityName) {
            $content = $content . '&name=' . $entityName;
        }

        if ($cardId) {
            $content = $content . '&cardId=' . $cardId;
        }

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates event where multiple properties of entity change their value
     *
     * @param $timestamp
     * @param $entityId
     * @param string|null $entityName
     * @param string|null $cardId
     * @return void
     */
    public function updateEntity($timestamp, $entityId, ?string $entityName, ?string $cardId)
    {
        $newEvent = new Event();
        $newEvent->setType('UP_EN');
        $content = 'id='. $entityId;

        if ($entityName) {
            $content = $content . '&name=' . $entityName;
        }

        if ($cardId) {
            $content = $content . '&cardId=' . $cardId;
        }

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates event where one property change it's value
     *
     * @param $timestamp
     * @param $entityId
     * @param $tagName
     * @param $tagValue
     * @return void
     */
    public function updateTag($timestamp, $entityId, $tagName, $tagValue)
    {
        $newEvent = new Event();
        $newEvent->setType('TAG');
        $content = 'entityId='. $entityId . '&tag='. $tagName. '&value='.  $tagValue;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);
    }
    
    /**
     * Creates event when mulligan options are displayed 
     *
     * @param $timestamp
     * @param $mulliganId
     * @param $entityIdArray
     * @return void
     */
    public function addMulliganEvent($timestamp, $mulliganId, $entityIdArray)
    {
        $newEvent = new Event();
        $newEvent->setType('MULLIGAN');
        $content = 'id='. $mulliganId;
        $idArray = '&entityId=';
        foreach ($entityIdArray as $id) {
            $idArray = $idArray . $id.',';
        }

        $idArray = rtrim($idArray,',');
        
        $content = $content . $idArray;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);

    }

    /**
     * Creates players choice from mulligan options
     *
     * @param $timestamp
     * @param $choiceId
     * @param $entityId
     * @return void
     */
    public function addChoice($timestamp, $choiceId, $entityId)
    {
        $newEvent = new Event();
        $newEvent->setType('CHOICE');
        $content = 'id='. $choiceId .'&entityId=' .$entityId;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates player moves picked from options
     *
     * @param $timestamp
     * @param $option
     * @param $target
     * @param $position
     * @return void
     */
    public function addSelect($timestamp, $option, $target, $position)
    {
        $newEvent = new Event();
        $newEvent->setType('SELECT');
        $content = 'option='. $option .'&target=' . $target . '&position='. $position;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates event where trippled card is changed into golden
     *
     * @param $timestamp
     * @param $entityId
     * @return void
     */
    public function addTriple($timestamp, $entityId)
    {
        $newEvent = new Event();
        $newEvent->setType('GOLDEN');
        $content = 'entity='. $entityId;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates event where triplled cards are removed form game 
     *
     * @param $timestamp
     * @param $entityId
     * @return void
     */
    public function addTripleMerge($timestamp, $entityId)
    {
        $newEvent = new Event();
        $newEvent->setType('MERGE');
        $content = 'entity='. $entityId;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);
    }

    /**
     * Hanndles combat events
     *
     * @param $timestamp
     * @param $vars
     * @return void
     */
    public function addCombat($timestamp, $vars)
    {
        $newEvent = new Event();
        $newEvent->setType('COMBAT');
        $content = $vars;

        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($content);

        //add event to container
        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates combat damage event
     *
     * @param $timestamp
     * @param $vars
     * @return void
     */
    public function addDamage($timestamp, $vars)
    {
        $newEvent = new Event();
        $newEvent->setType('DAMAGE');
        $newEvent->setTimestamp($timestamp);
        $newEvent->setContent($vars);

        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates game over event
     *
     * @param $timestamp
     * @return void
     */
    public function addGameFinish($timestamp)
    {
        $newEvent = new Event();
        $newEvent->setType('FINISH');
        $newEvent->setTimestamp($timestamp);
        
        $this->addEventToContainer($newEvent);
    }

    /**
     * Creates event where available moves are displayed to player
     *
     * @param $timestamp
     * @param $optionBlock
     * @return void
     */
    public function addOptionBlock($timestamp, $optionBlock)
    {
        foreach ($optionBlock->getOptions() as $option) {
            $content = null;

            $newEvent = new Event();
            $newEvent->setType('OPTION');
            $newEvent->setTimestamp($timestamp);

            $content ='id='. $optionBlock->getBlockId();
                
            $content = $content . '&opId='. $option->getOptionNumber();

            if($option->getMainEntity() != null) {
                $mainEntityId = $option->getMainEntity()->getId();

                $content = $content . '&mainEntityId='. $mainEntityId;

                $content = $content . '&player='. $option->getPlayerId() . '&zone='. $option->getZone().'&pos=' . $option->getZonePosition();

                $newEvent->setContent($content);
                $this->addEventToContainer($newEvent);

                //adding targets
                if($option->getTargets() != null){

                    foreach ($option->getTargets() as $target) {
                        $content= null;
                        $newTargetEvent = new Event();

                        $newTargetEvent->setType('TARGET');
                        $newTargetEvent->setTimestamp($timestamp);

                        $content ='blockId='. $optionBlock->getBlockId();

                        $content = $content . '&opId='. $option->getOptionNumber() . '&tarId='. $target->getOptionNumber();

                        $targetEntityId = $target->getMainEntity()->getId();

                        $content = $content . '&targetEntityId='. $targetEntityId;

                        $content = $content . '&player='. $target->getPlayerId() . '&zone='. $target->getZone().'&pos=' . $target->getZonePosition();
            
                        $newTargetEvent->setContent($content);
                        $this->addEventToContainer($newTargetEvent);
                    }
                }
            }
        }

        $newEvent = new Event();
        $newEvent->setType('OPTION');
        $newEvent->setTimestamp($timestamp);
        
        $content ='id='. $optionBlock->getBlockId().'&finish';
        $newEvent->setContent($content);
        $this->addEventToContainer($newEvent);
    }
}