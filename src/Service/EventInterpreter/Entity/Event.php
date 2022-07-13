<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\EventInterpreter\Entity\CardCollection;
use App\Service\LogCrawler\Entities\Entity;

//basic event class, used for every event that is not player action

class Event implements \ArrayAccess
{
    protected $id;

    protected $timestamp;

    protected $content;

    protected $type;

    private $playerHealth;

    protected $playerBoard;

    protected $secondBoard;

    protected $playerHand;

    protected $playerGold;

    protected $tavernTier;

    protected $upgradeCost;

    protected $rerollCost;

    protected $heroPowerUsed;

    protected $target;


    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->$id;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setContent($text)
    {
        $this->content = $text;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setPlayerBoard($board)
    {
        $this->playerBoard = $board;
    }

    public function getPlayerBoard()
    {
        return $this->playerBoard;
    }

    public function setSecondBoard($board)
    {
        $this->secondBoard = $board;
    }

    public function getSecondBoard()
    {
        return $this->secondBoard;
    }

    public function setHand($hand)
    {
        $this->playerHand = $hand;
    }

    public function getHand()
    {
        return $this->playerHand;
    }

    public function setPlayerHealth($hp)
    {
        $this->playerHealth = $hp;
    }
        
    public function getPlayerHealth()
    {
        return $this->playerHealth;
    }

    public function setPlayerGold($gold)
    {
        $this->playerGold = $gold;
    }

    public function getPlayerGold()
    {
        return $this->playerGold;
    }

    public function getTavernTier()
    {
        return $this->tavernTier;
    }

    public function setTavernTier($tavernTier)
    {
        $this->tavernTier = $tavernTier;
    }

    public function setUpgradeCost($cost)
    {
        $this->upgradeCost = $cost;
    }

    public function getUpgradeCost()
    {
        return $this->upgradeCost;
    }

    public function setRerollCost($cost)
    {
        $this->rerollCost = $cost;
    }

    public function getRerollCost()
    {
        return $this->rerollCost;
    }
        
    public function setHeroPowerUsed($bool)
    {
        $this->heroPowerUsed = $bool;
    }

    public function getHeroPowerUsed()
    {
        return $this->heroPowerUsed;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getTarget()
    {
        return $this->target;
    }
        
    public function setCardCollections($playerBoard, $secondBoard, $hand)
    {
        $this->playerBoard =  $playerBoard;
        $this->secondBoard =  $secondBoard;
        $this->playerHand =  $hand;

    }

    public function promptCollection(?array $cardCollection)
    {
        $content = $this->content;

            if($cardCollection !=null) {
            foreach ($cardCollection as $card) {
                $content = $content . "<br>" . $card->getName();
                
                if ($card->isGolden()) {
                    $content = $content.' (G)';
                }

                if ($card->getTripleCheck()) {
                    $content = $content.' (!)';
                }
            }
            $this->content = $content;
        }
        return $this->content;
    }

    public function __toString()
    {
        $content =$this->content;

        if ($this->playerBoard != null) {
            
            $this->content = $this->content ."<br> <b>Player Board</b>:";

            $this->promptCollection($this->playerBoard);
        }
        
        if ($this->secondBoard != null) {

            $this->content = $this->content ."<br> <b>Shop:</b>";

            $this->promptCollection($this->secondBoard);
            
        }

        if ($this->secondBoard != null) {

            $this->content = $this->content ."<br> <b>Hand:</b>";

            $this->promptCollection($this->playerHand);
            
        }

        return $this->timestamp . '-----'. $this->type .'----' .  $this->content;
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }
    public function offsetGet($offset)
    {
        return $this->$offset;
    }
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}