<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Hand;
use App\Service\EventInterpreter\Entity\Board;

class Player
{
    private $name;

    private $playerId;

    private $hero;

    private $heroPower;

    private $tavernLvl = 1;

    private $triples = 0;

    private $streak;

    private $board;

    private $hand;

    private $health = 40;


    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setHero($entity)
    {
        $this->hero = $entity;
    }

    public function getHero()
    {
        return $this->hero;
    }

    public function setHeroPower($entity)
    {
        $this->heroPower = $entity;
    }

    public function getHeroPower()
    {
        return $this->heroPower;
    }

    public function setTriples($triples)
    {
        $this->triples = $triples;
    }

    public function getTriples()
    {
        return $this->triples;
    }

    public function setPlayerId($playerId)
    {
        $this->playerId = $playerId;
    }

    public function getPlayerId()
    {
        return $this->playerId;
    }

    public function getHand()
    {
        return $this->hand;
    }

    public function addToHand($entity)
    {
        $this->hand[$entity->getId()] = clone $entity;
    }

    public function findCardInHand($id)
    {
        if (isset($this->hand[$id])) {
            return true;
        }
        return false;
    }

    public function removeCardFromHand($entity)
    {
       if (isset($this->hand[$entity->getId()])) {
           unset($this->hand[$entity->getId()]);
       }
    }
    public function findCardOnBoard($id)
    {
        if (isset($this->board[$id])) {
            return true;
        }
        return false;
    }

    public function getCardFromBoard($id)
    {
        if (isset($this->board[$id])) {
            return $this->board[$id];
        }
    }

    public function removeCardFromBoard($entity)
    {
       if (isset($this->board[$entity->getId()])) {
           unset($this->board[$entity->getId()]);
       }
    }

    public function clearBoard()
    {
        $this->board = array();
    }

    public function addToBoard($entity)
    {
        $zonePos = $entity->getPosition();
        $card = clone $entity;
        if ($zonePos == 0) {
            array_push($this->board, $card);
        } elseif (isset($this->board[$zonePos])) {
            $newBoard = array_merge( 
            array_slice($this->board, 0, $zonePos -1),
            [$zonePos => $card],
            array_slice($this->board, $zonePos -1)
            );

            $this->board = $newBoard;
        } else {
            $this->board[$zonePos] = $card;
        }
    }

    public function getBoard()
    {
        if (is_array($this->board)) {
            ksort($this->board);
        }
        
        return $this->board;
    }

    public function addToBoardByPosition($card)
    {
        $zoenPos = $card->getPosition();

        if ($zoenPos == 0) {
            array_push($this->board, $card);
        } elseif (isset($this->board[$zoenPos])) {
            $newBoard = array_merge( 
            array_slice($this->board, 0, $zonePos),
            $card,
            array_slice($this->board, $zonePos)
            );

            $this->board = $newBoard;
        } else {
            $this->board[$zoenPos] = $card;
        }
    }

    public function sortBoardByPosition()
    {
        $positionedArray = array();

        if ($this->board !=null) {
            foreach ($this->board as $card) {
                $pos = $card->getPosition();
                
                if (isset($positionedArray[$pos])) {
                    array_push($positionedArray,$card);
                } else {
                    $positionedArray[$pos] = $card;
                }
            }
            ksort($positionedArray);
            return $positionedArray;
        } else {
            return [];
        }
    }

    public function collectionUpdate($card)
    {
        if($this->findCardOnBoard($card->getId()))
        {
            $this->removeCardFromBoard($card);
            $this->addToBoard($card);
        } elseif ($this->findCardInHand($card->getId())) {
            $this->removeCardFromHand($card);
            $this->addToHand($card);
        }
    }

    public function setHealth($hp)
    {
        $this->health = $hp;        
    }
        
    public function getHealth()
    {
        return $this->health;
    }

    public function setTavernLvL($lvl)
    {
        $this->tavernLvl = $lvl;
    }

    public function getTavernLvl()
    {
        return $this->tavernLvl;
    }

    public function setStreak($filename)
    {
        $this->streak = $filename;
    }      
}