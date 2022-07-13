<?php

namespace App\Service\EventInterpreter\Entity;

use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventInterpreter\Entity\Mulligan;
use App\Service\EventInterpreter\Entity\Round;
use App\Service\EventInterpreter\Entity\Card;
use App\Service\EventInterpreter\Entity\Combat;
use App\Service\EventInterpreter\GameHistoryService;

class Game
{
    private $id;

    private $crawlerGameEntity;

    private $owner = null;

    private $innkeeper;

    private $rounds;

    private $currentRoundNr = 0;

    private $currentOptionBlock;

    private $optionBlockArray = array();

    private $players = array();

    private $mulliganArray = array();

    private $tribes = array();

    private $leaderboard = array();

    private $gameHistoryService ;

    private $gameEvents = array();

    private $cardArray = array();

    private $nextForCombat = null;

    private $combatQueue = array();

    private $playerGold;

    private $resources;

    private $tempResources;

    private $usedResources;

    private $combatArray = array();

    private $rerollCost;

    private $tavernUpgrade;

    private $healthSnapshot = array();

    private $finalPlacement;

    private $heroPowerArray = array();
    private $previousOpponent;

    public function __construct()
    {
        $this->rounds[$this->currentRoundNr] = new Round();
        $this->rounds[$this->currentRoundNr]->setRoundNumber($this->currentRoundNr);
        $this->gameHistoryService = new GameHistoryService();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCrawlerGameEntity($crawlerGameEntity)
    {
        $this->crawlerGameEntity = $crawlerGameEntity;
    }

    public function getCrawlerGameEntity()
    {
        return $this->crawlerGameEntity;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        if (isset($this->owner)) {
            return $this->owner;
        } else {
            return false;
        }
     
        
    }

    public function setInnkeeper($player)
    {
        $this->innkeeper = $player;
    }

    public function getInnkeeper()
    {
        return $this->innkeeper;
    }

    public function addPlayer(Player $player)
    {
        $this->players[$player->getPlayerId()] = $player;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function findPlayerByEntityId($id){
        foreach ($this->players as $player) {
            if($player->getHero()->getId() == $id)
            {
                return true;
            }
        }

        return false;
    }

    public function getFinalPlacement()
    {
        return $this->finalPlacement;
    }
  
    public function getPlayerById($playerId)
    {
        if (isset($this->players[$playerId])) {

            return $this->players[$playerId];
        
        } else {
            return false;
        }
        
    }

    public function addMulligan(Mulligan $mulligan)
    {
        $id = $mulligan->getId();
        $this->mulliganArray[$id] = $mulligan;
    }

    public function getMulliganById($id)
    {
        if (isset($this->mulliganArray[$id])){
            return $this->mulliganArray[$id];
        } else {
            return null;
        }
    }

    public function getGameHistoryService()
    {
        return $this->gameHistoryService;
    }

    public function getGameHistoryServiceArray()
    {
        return $this->gameHistoryService->getEventArray();
    }

    public function addGameEvent($event)
    {
        array_push($this->gameEvents, $event);
    }

    public function getGameEvents()
    {
        return $this->gameEvents;
    }

    public function nextRound($prevOppId)
    {
        if (isset($this->rounds[$this->currentRoundNr-1])){
            $this->rounds[$this->currentRoundNr]->copyLeaderboard($this->generateFullLeaderboard());
        }

        if ($this->rounds[$this->currentRoundNr]->getNextOpponent() == null) {
            $this->rounds[$this->currentRoundNr]->setNextOpponent($prevOppId);
            $this->previousOpponent = $prevOppId;
        } else {
            $this->previousOpponent = $this->rounds[$this->currentRoundNr]->getNextOpponent();
        }

        $this->currentRoundNr++;
        
        $nextRound = new Round();
        $this->makeHealthSnapshot();


        $nextRound->setRoundNumber($this->currentRoundNr);

        if(isset($this->combatArray[$this->currentRoundNr])){
            $nextRound->setCombat($this->combatArray[$this->currentRoundNr]);
        }
        
        if($this->currentRoundNr > 0){
            $this->addToCombatQueue($nextRound);
        }
        
        $this->rounds[$this->currentRoundNr] = $nextRound;
        
        return $this->currentRoundNr;
    }

    public function getRounds()
    {
        return $this->rounds;
    }

    public function getRoundByNr($rndNr)
    {
        return $this->rounds[$rndNr];
    }

    public function getCurrentRoundNr()
    {
        return $this->currentRoundNr;
    }

    public function getCurrentRoundNrObject()
    {
        return $this->rounds[$this->currentRoundNr];
    }

    public function getPreviousRoundObject()
    {
        return $this->rounds[$this->currentRoundNr - 1];
    }

    public function addToLeaderboard($playerId, $value)
    {
        $this->leaderboard[$playerId] = $value;
    }

    public function setCurrentOptionBlock($optionBlock)
    {
        $this->currentOptionBlock = $optionBlock;
        $this->rounds[$this->currentRoundNr]->addOptionBlock($this->currentOptionBlock);
    }

    public function getCurrentOptionBlock()
    {
        return $this->currentOptionBlock;
    }

    public function addCardToArray($card)
    {
        $this->cardArray[$card->getId()] = $card;
    }

    public function findCardInArray($id)
    {
        if(isset($this->cardArray[$id]))
        {
            return $this->cardArray[$id];
        } else {
            return false;
        }
    }
    
    public function setCurrentCombat($combat)
    {
        $this->currentCombat = $combat;
    }

    public function getCombat($combat)
    {
        return $this->currentCombat;
    }

    public function addCombat($combat)
    {
        $this->combatArray[$combat->getId()] = $combat;
        
        $this->updateCombat($combat);
       if(isset($this->rounds[$combat->getId()])){
            $this->rounds[($combat->getId())]->setCombat($combat);
        }
    }
 
    public function getCombatById($id)
    {
        if(isset($this->rounds[$id])){
            return $this->rounds[$id]->getCombat();
        } else return null;
        
    }


    public function addToCombatQueue($round)
    {
        if ($this->nextForCombat == null){
            $this->nextForCombat = $round->getRoundNumber();
        } else {
            array_push($this->combatQueue, $round->getRoundNumber());
        }
    }

    public function updateCombat($combat)
    {
        if($this->nextForCombat != null){

            $this->rounds[$this->nextForCombat]->setCombat($combat);

            if($this->combatQueue != null) {
                $this->nextForCombat = $this->combatQueue[array_key_first($this->combatQueue)];
                unset($this->combatQueue[array_key_first($this->combatQueue)]);
            }
        }
    }

    public function getCombatArray()
    {
        return $this->combatArray;
    }
    

    public function checkUpdateBoards($card)
    {
        $this->getOwner()->collectionUpdate($card);
        $this->getInnkeeper()->collectionUpdate($card);
    }

    public function getPlayerGold()
    {
        $this->playerGold = $this->resources + $this->tempResources - $this->usedResources;

        return $this->playerGold;
    }

    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function setTempResources($tempResources)
    {
        $this->tempResources = $tempResources;
    }

    public function getTempResources()
    {
        return $this->tempResources;
    }

    public function setUsedResources($usedResources)
    {
        $this->usedResources = $usedResources;
    }

    public function getUsedResources()
    {
        return $this->usedResources;
    }

    public function setRerollCost($cost)
    {
        $this->rerollCost = $cost;
    }

    public function getRerollCost()
    {
        return $this->rerollCost;
    }

    public function setTavernUpgradeCost($cost)
    {
        $this->tavernUpgrade = $cost;
    }

    public function getTavernUpgradeCost()
    {
        return $this->tavernUpgrade;
    }

    public function addTribe($tribe)
    {
        array_push($this->tribes, $tribe);
    }

    public function getTribes()
    {
        return $this->tribes;
    }

    public function hasTribe($value)
    {
        foreach ($this->tribes as $tribe) {
           
            if ($tribe == $value) {
                return true;
            } 
        }

        return false;
    }

    private function generateFullLeaderboard()
    {
        $currentLb = $this->leaderboard;

        asort($currentLb); //[$cardId, $playerHealth, $playerTrips]

        $fullLeaderboard = array(); 
        foreach ($currentLb as $key => $value) {
            $currentPlace = array();
            $player = $this->getPlayerById($key);
            $currentPlace = [
                $player->getHero(),
                $this->healthSnapshot[$key],
                $player->getTriples(),
                $key,
            ];

            array_push($fullLeaderboard, $currentPlace);
        }

        return $fullLeaderboard;
    }

    public function gameOver($timestamp)
    {
        $this->finalPlacement =  $this->leaderboard[$this->owner->getPlayerId()];
    }

    private function makeHealthSnapshot()
    {
        foreach ($this->players as $player) {
            $this->healthSnapshot[$player->getPlayerId()] = $player->getHealth();
        }
    }

    public function createLastRoundLeaderboard()
    {
        if (isset($this->rounds[$this->currentRoundNr-1])){
            $this->rounds[$this->currentRoundNr]->copyLeaderboard($this->generateFullLeaderboard());
        }
        if ($this->rounds[$this->currentRoundNr]->getNextOpponent() == null) {
            $this->rounds[$this->currentRoundNr]->setNextOpponent($this->previousOpponent);
            //dd($this->rounds[$this->currentRoundNr]);
        }
    }

    public function updateHeroPower($heroPower, $playerId){

        if ((isset($this->heroPowerArray[$playerId])
        && $this->heroPowerArray[$playerId] != $heroPower) ||
        !(isset($this->heroPowerArray[$playerId]))
        ) {
            $this->heroPowerArray[$playerId] = $heroPower;
        }
    }
}