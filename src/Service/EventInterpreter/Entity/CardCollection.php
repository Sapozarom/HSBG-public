<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Card;

class CardCollection
{
    protected $cardArray = array();

    protected $limit = array();

    protected $card;

    public function __construct(Card $card)
    {
        $this->card = $card;
    }

    public function addCard($card)
    {
        $id = $card->getId();

        if(isset($this->cardArray[$id]))
        {
            $this->cardArray[$id] = $card;
        }
    }

    public function removeCard($card)
    {
        $id = $card->getId();

        if(isset($this->cardArray[$id]))
        {
            unset($this->cardArray[$id]);
        }
    }

    public function getCards()
    {
        return $this->cardArray;
    }

    public function getCardByPosition($position)
    {
        if(isset($this->cardArray[$position]))
        {
            return $this->cardArray[$position];
        }
    }

    public function __toString()
    {
        $positionArray = array();
        if ($this->cardArray !=null)
        {
            foreach ($this->cardArray as $card) {
               
                if($card->getPosition() != null && isset($positionArray[$card->getPosition()]))
                {
                    $positionArray[$card->getPosition()] = $card;
                }
            }

            ksort($positionArray);
            $content = '';
            foreach ($positionArray as $key => $card) {
                $content = $content . $key. '. '. $card->getName() . '<br> ';
            }
        }

        return $content;
    }
}