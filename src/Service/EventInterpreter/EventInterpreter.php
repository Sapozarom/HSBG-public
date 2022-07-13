<?php

namespace App\Service\EventInterpreter;

use App\Service\EventCollector\Entities\Event;
use App\Service\EventCollector\EventCollector;
use App\Service\LogCrawler\Entities\Entity;
use App\Service\LogCrawler\Crawler;
use App\Service\EventInterpreter\Entity\Option;
use App\Service\EventInterpreter\Entity\OptionBlock;
use App\Service\EventInterpreter\Entity\Game;
use App\Service\EventInterpreter\Entity\Player;
use App\Service\EventInterpreter\Entity\Mulligan;
use App\Service\EventInterpreter\Entity\Board;
use App\Service\EventInterpreter\Entity\Card;
use App\Service\EventInterpreter\Entity\HeroPower;
use App\Service\EventInterpreter\Entity\SelectedOption;
use App\Service\EventInterpreter\Entity\Combat;

use Symfony\Component\Filesystem\Filesystem;

/**
 * This service takes data form EventCollector, represents them as objects and store them as player moves or game events
 */
class EventInterpreter {
    
    private $eventCollector;

    private $entity;

    private $game;

    private $gameFlag = false;

    private $file;

    private $entityContainer = array();

    private $entityByName = array();

    private $mulliganArray = array();

    private $choiceArray = array();

    private $nextAction = null;

    private $nextActionTarget = null;

    private $nextActionTimestamp = null;

    private $nextActionPosition = 0;

    private $optionBlockArray = array();

    private $gameHistoryService;

    private $currentCombat;

    private $mergedCards = array();

    private $tempUsedResources = null;

    private $heroPowerUsed = false;

    private $disconected = array();

    private $lastRoundSumarized = false;

    private $previousOpponent;


    public function __construct()
    {
        $this->gameHistoryService = new GameHistoryService();
        $this->currentCombat = new Combat;
    }

    /**
     * Returns main game object
     *
     * @return Game 
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * Returns Entity from array of objects
     *
     * @param Int $id
     *
     * @return Entity||bool
     */
    public function getEntityFromContatiner($id)
    {
        if (isset($this->entityContainer[$id])) {
            return $this->entityContainer[$id];
        } else {
            return false;
        }
    }
    
    /**
     * Main function that runs through data scraped from game file and triggers proper event processing
     *
     * @param String $filename | name of processed file
     * @param String $path  | localisation of file
     * @return Game 
     */
    public function interpreteGame($filename, $path)
    {
        $this->filesystem = new Filesystem();

        //create new Game object
        $this->game = new Game();

        $this->file = $filename;
        $this->optionBlockArray = array();
        $mergedCards = array();

        //service that runs through file and groups data into blocks that represents specific event
        $crawler = new Crawler();
        $crawler->crawlThroughFile($filename, $path);

        $eventContainer = $crawler->getEventCollector();

        foreach($eventContainer as $event) {
            
            //switching through diferent Event types
            switch ($event->getType()) {
                case 'FINISH':
                    $this->game->gameOver($event->getTimestamp());
                    break;
                case 'CR_EN':
                    $this->createEntity($event);
                    break;
                case 'UP_EN':
                    $this->updateEntity($event);
                    break;
                case 'TAG':
                    $this->updateTag($event);
                    break;
                case 'MULLIGAN':
                    $this->manageMulligan($event);
                    break;
                case 'CHOICE':
                    $this->manageChoice($event);
                    break;
                case 'OPTION':
                    $this->manageOption($event);
                    break; 
                case 'TARGET':
                    $this->manageTarget($event);
                    break; 
                case 'SELECT':
                    $this->manageSelectOption($event);
                    break;        
                case 'GOLDEN':
                    $this->manageGolden($event);
                    break;
                case 'MERGE':
                    $this->manageTripleMerge($event);
                    break;
                case 'COMBAT':
                    $this->manageCombat($event);
                    break;    
                case 'DAMAGE':
                    $this->manageDamage($event);
                    break;  
            }
        }

        //add events that happened before firs round
        $this->addStartEvents();
        
        $eventContainer = array();

        $this->createCombatEvents();  

        return $this->game;
         
    }
    /**
     * Function that handle creating new Entity that showed for the first time in logs
     *
     * @param Event $event
     * @return void
     */
    public function createEntity(Event $event)
    {

        $content = $event->getContent();

        parse_str($content, $variables);

        $newEntity = new Entity();
        
        if (isset($variables['id']))
        {
            $newEntity->setId($variables['id']);
        }

        if (isset($variables['cardId'])) {

            $newEntity->setCardId($variables['cardId']);

            if ($variables['cardId'] == 'TB_BaconShop_8p_Reroll_Button') {
                
                $rndNr = $this->game->nextRound($this->previousOpponent);
                $this->heroPowerUsed = false;
            }
        }

        if (isset($variables['name'])) {

            $newEntity->setName($variables['name']);

            if ($variables['name'] == 'GameEntity') {
                
                $this->game->setCrawlerGameEntity($newEntity);
                
                $event = $this->gameHistoryService->historyEvent('GAME', $event->getTimestamp(), 'Game started!');
                
                $this->addGameEvent($event);
            }
  
            if ($variables['name'] == 'Player') {

                $this->entityContainerByName['Player'][$variables['id']] = $newEntity;
            }
        }
        $this->entityContainer[$variables['id']] = $newEntity;
    }

    /**
     * Updates properties of existing Entity
     *
     * @param Event $event
     * @return void
     */
    public function updateEntity (Event $event)
    {
        $content = $event->getContent();

        parse_str($content, $variables);

        if (isset($variables['id']) && $this->getEntityFromContatiner($variables['id'])) {

            $entity = $this->getEntityFromContatiner($variables['id']);

            if (isset($variables['name'])) {

                $entity->setName($variables['name']);

                if($variables['name'] == 'The Innkeeper') {
                    $player = new Player();
                    $player->setName('The Innkeeper');
                    $player->setPlayerId($this->getEntityFromContatiner($variables['id'])->getTagValueFromPairArray('PLAYER_ID'));
                    $this->game->setInnkeeper($player);
                }
                
                if($this->game->findCardInArray($variables['id'])) {
                    $this->updateCard($variables['id'], 'NAME' , $variables['name']);
                }
            }
    
            if (isset($variables['cardId'])) {
                $entity->setCardId($variables['cardId']);
            }
        }    
    }
    /**
     * Updating one specific property of existing Entity
     *
     * @param Event $event
     * @return void
     */
    public function updateTag(Event $event)
    {
        $content = $event->getContent();

        parse_str($content, $variables);

        if (isset($variables['entityId']) && $this->getEntityFromContatiner($variables['entityId']) ) {
            
            $entity = $this->getEntityFromContatiner($variables['entityId']);
            $entity->updateTagInArray($variables['tag'], $variables['value']);

            if($this->game->findCardInArray($variables['entityId'])) {
                $this->updateCard($variables['entityId'], $variables['tag'], $variables['value']);
            }

            $this->observeTagUpdate($this->getEntityFromContatiner($variables['entityId']), $variables['tag'],$variables['value'], $event->getTimestamp());
        }
    }

    /**
     * Handles information about choices presented by game in "Mulligan phase"
     *
     * @param Event $event
     * @return void
     */
    public function manageMulligan(Event $event)
    {
        $content = $event->getContent();

        parse_str($content, $variables);
        
        $entityArray = explode (',', $variables['entityId']);
        
        if (isset($variables['id'])) {
            $newMulligan = new Mulligan();
            $newMulligan->setId($variables['id']);

            foreach ($entityArray as $heroId) {
               if($this->entityContainer[$heroId]) {
                    $newMulligan->addOption($this->entityContainer[$heroId]);
               }
            }

            $this->game->addMulligan($newMulligan);
        }
    }

    /**
     * Handles player choice and assign it to the specific "Mulligan phase"
     *
     * @param Event $event
     * @return void
     */
    private function manageChoice(Event $event)
    {
        $content = $event->getContent();

        parse_str($content, $variables);

        if(isset($variables['id']) && isset($variables['entityId'])) {
            if(!is_null($this->game->getMulliganById($variables['id'])) && isset($this->entityContainer[$variables['entityId']])) {
                $this->game->getMulliganById($variables['id'])->setChoice($this->entityContainer[$variables['entityId']]);

                $mulliganType = $this->game->getMulliganById($variables['id'])->establishMulliganType();

                $secondBoard = array();
                
                foreach ($this->game->getMulliganById($variables['id'])->getOptions() as $option) {
                    if ($this->game->findCardInArray($option->getId())) {
                        array_push($secondBoard, $this->game->findCardInArray($option->getId()));
                    }   
                }

                $choiceId = $this->game->getMulliganById($variables['id'])->getChoice()->getId();

                $choice = [$this->game->findCardInArray($choiceId)];
                
                $gameEvent = $this->gameHistoryService->historyEvent(
                    'MULLIGAN',  //event type
                    $event->getTimestamp(), //timestamp 
                    $mulliganType , //text\content
                    null, //owner board
                    $secondBoard, //second board - mulligan options
                    $choice,  //hand - mulligan pick
                    null, //gold 
                    null, //tavern lvl 
                    null, //reroll cost 
                    null  //tavern upgrade cost 
                );

                $this->addGameEvent($gameEvent);
                
                if($mulliganType == 'HERO') {
                    $this->game->getOwner()->setHero($this->entityContainer[$variables['entityId']]->getCardId());
                }
            }
        }
    }

    /**
     * Handles information about possible player moves during the round
     *
     * @param Event $event
     * @return void
     */
    private function manageSelectOption(Event $event)
    {
        $content = $event->getContent();

        parse_str($content, $variables);

        $select = new SelectedOption();

        $select->setTimestamp($event->getTimestamp());
        $select->setOptionBlock($this->game->getCurrentOptionBlock()); 
        $select->setSelectedOption($variables['option']);
        $select->setSelectedTarget($variables['target']);
        $select->setSelectedPosition($variables['position']);
        $this->game->getCurrentOptionBlock()->setSelect($select);
        
        $option = $this->game->getCurrentOptionBlock()->getOption($variables['option']);
        
        $this->nextActionTimestamp = $event->getTimestamp();

        switch ($option->getMainEntity()->getCardId()) {
            case 'TB_BaconShop_DragBuy':
                $card = $this->game->findCardInArray($variables['target']);
                $this->game->getInnkeeper()->removeCardFromBoard($card);
                
                if (!$card->getTripleCheck()) {
                    $this->game->getOwner()->addToHand($card);  
                }

                $event = $this->gameHistoryService->historyEvent('GAME',$event->getTimestamp(), 'player BOUGHT minion: ' . $card->getName());
                $this->nextAction = 'BOUGHT';
                $this->nextActionTarget = clone $card;
                break;
            case 'TB_BaconShop_DragSell':
                $card = $this->game->findCardInArray($variables['target']);
                $event = $this->gameHistoryService->historyEvent('GAME',$event->getTimestamp(), 'player SOLD minion: ' . $card->getName());
                $this->nextAction = 'SOLD';
                $this->nextActionTarget = clone $card;
                break;
            case 'TB_BaconShop_8p_Reroll_Button':
                $card = $this->game->findCardInArray($variables['target']);
                $event = $this->gameHistoryService->historyEvent('GAME',$event->getTimestamp(), 'Shop refresh');
                $this->nextAction ='REFRESH';
                break;
            case 'TB_BaconShopLockAll_Button':
                $card = $this->game->findCardInArray($variables['target']);
                $this->nextAction ='FREEZE';
                $event = $this->gameHistoryService->historyEvent('GAME',$event->getTimestamp(), 'Shop freeze');
                break;
            case 'TB_BaconShopTechUp02_Button':    
            case 'TB_BaconShopTechUp03_Button':
            case 'TB_BaconShopTechUp04_Button':
            case 'TB_BaconShopTechUp05_Button':
            case 'TB_BaconShopTechUp06_Button':
            case 'TB_BaconShopTechUp07_Button':
                $event = $this->gameHistoryService->historyEvent('GAME',$event->getTimestamp(), 'Tavern Upgrade');
                $this->nextAction ='TAVERN';
                break;
            default:
                $cardName = $option->getMainEntity()->getName();
                $card = $this->game->findCardInArray($option->getMainEntity()->getId());
                if ($card != false) {

                    if ($card->getType() == 'HERO_POWER') {

                        $this->heroPowerUsed = true;
                        $this->nextAction ='USED';
                        $this->nextActionTarget = clone $card;

                    } elseif ($this->game->getCurrentOptionBlock()->findShopPosition() > $variables['option']) {

                        $this->game->getOwner()->removeCardFromHand($card);
                        $this->nextAction ='PLAYED';
                        $this->nextActionTarget = clone $card;
                        $this->nextActionPosition = $variables['position'];
                        
                    } else { 
                        
                        $event = $this->gameHistoryService->historyEvent('GAME',$event->getTimestamp(), 'player MOVED: ' . $card->getName());
                        $this->nextAction ='MOVED';
                        $this->nextActionTarget = clone $card;
                    }
                }
                break;
        }
    }

    /**
     * Process player move
     *
     * @param Event $event
     * @return void
     */
    private function manageOption(Event $event)
    {
        $content = $event->getContent();
        
        parse_str($content, $variable);

        //creating option block 
        if (isset($this->optionBlockArray[$variable['id']])) {
            
            $optionBlock = $this->optionBlockArray[$variable['id']];
            
        } else {

            $optionBlock = new OptionBlock();
            $optionBlock->setBlockId($variable['id']);
            $this->optionBlockArray[$variable['id']] = $optionBlock;
            $this->game->setCurrentOptionBlock($optionBlock); 

        }

        //block finish
        if (isset($variable['finish'])) {
            //update boards and hand
            if ($this->game->getCurrentRoundNrObject()->countOptions() === 1) {
                
                //Start of new round 
                $this->parseCurrentOptionBlock();
                $event = $this->gameHistoryService->historyEvent('BOARD', $event->getTimestamp(), 'Round '. $this->game->getCurrentRoundNr() .' started!', $this->game->getOwner()->getBoard(), $this->game->getInnkeeper()->getBoard(), $this->game->getOwner()->getHand(),  $this->game->getPlayerGold(), $this->game->getOwner()->getTavernLvl(), $this->game->getRerollCost(), $this->game->getTavernUpgradeCost(), $this->heroPowerUsed, null , $this->game->getOwner()->getHealth());          
                $this->addGameEvent($event);

            } else {

                $this->parseCurrentOptionBlock();

                $event = $this->gameHistoryService->historyEvent('BOARD', $this->nextActionTimestamp, $this->nextAction, $this->game->getOwner()->getBoard(), $this->game->getInnkeeper()->sortBoardByPosition(), $this->game->getOwner()->getHand(),  $this->game->getPlayerGold(), $this->game->getOwner()->getTavernLvl(),  $this->game->getRerollCost(), $this->game->getTavernUpgradeCost(), $this->heroPowerUsed, $this->nextActionTarget, $this->game->getOwner()->getHealth());

                $this->addGameEvent($event);
                $this->nextAction = null;
                $this->nextActionTarget = null;
                $this->nextActionTimestamp = null;
                $this->nextActionPosition = 0;

            }
            
        } else {
            $option = new Option();

            if ( isset($variable['opId']) ) {
                $option->setOptionNumber($variable['opId']);
            }

            if( isset($variable['mainEntityId']) && isset($this->entityContainer[$variable['mainEntityId']]) ) {
                $option->setMainEntity($this->entityContainer[$variable['mainEntityId']]);
            }

            if ( isset($variable['player']) ) {
                $option->setPlayerId($variable['player']);
            }

            if ( isset($variable['zone']) ) {
                $option->setZone($variable['zone']);
            }

            if ( isset($variable['pos']) ) {
                $option->setZonePosition($variable['pos']);
            }

            $optionBlock->addOption($option);
        }
    }

    /**
     * Handles target of player move
     *
     * @param Event $event
     * @return void
     */
    private function manageTarget($event)
    {
        $content = $event->getContent();
        
        parse_str($content, $variable);

        if ( isset($variable['blockId']) && isset($variable['opId']) ) {
            $optionBlock = $this->optionBlockArray[$variable['blockId']];

            $option = $optionBlock->getOption($variable['opId']);

            $target = new Option();

            if( isset($variable['targetEntityId']) && isset($this->entityContainer[$variable['targetEntityId']]) ) {
                $target->setMainEntity($this->entityContainer[$variable['targetEntityId']]);
            }

            if ( isset($variable['player']) ) {
                $target->setPlayerId($variable['player']);
            }

            if ( isset($variable['zone']) ) {
                $target->setZone($variable['zone']);
            }

            if ( isset($variable['pos']) ) {
                $target->setZonePosition($variable['pos']);
            }

            $option->addTarget($target);
        }

    }   


    public function getGameEvents()
    {
        $eventArray = array();

        foreach($this->game->getRounds() as $round) {
            foreach ($round->getEvents() as $event) {
                array_push($eventArray, $event);
            }
        }
        return $eventArray;
    }

    /**
     * Handle "Combat phase"
     *
     * @return void
     */
    private function createCombatEvents()
    {
        $previousOpponent = null;

        foreach ($this->game->getRounds() as $round) {
            if ($round->getRoundNumber() != 0 && $round->getCombat() != null) {

                $combat = $round->getCombat();
                
                $timestamp = $combat->getMainEntity();
                
                $text = null;
    
                $playerBoard = $combat->getPlayerBoard();
    
                $secondBoard = $combat->getOppBoard();
    
                //winner
                if ($combat->getTargets() == null) {
                    $winner = null;
                } else {
                    $targetArray = $combat->getTargets();
    
                    $winnerId = $targetArray[0]->getPlayerId();
                    
                    if ($winnerId == $this->game->getOwner()->getPlayerId()) {
                        $winner = $this->game->getOwner()->getName();
                    } else {
                        $playerId = $combat->getOppPlayerId();

                        $winner = $this->game->getPlayerById($playerId)->getName();
                    }
                }

                //damage

                if ($combat->getDamageArray() != null) {
                    
                    $dmagArray = $combat->getDamageArray();

                    $damage = end($dmagArray);

                } else {
                    $damage = 0;
                }

                //opponent name
                $oppPlayerId = $combat->getOppPlayerId();

                if($oppPlayerId == null)
                {
                    $oppPlayerId = $previousOpponent;
                    
                } else {
                    $previousOpponent = $oppPlayerId;
                }
                $opponent = $this->game->getPlayerById($oppPlayerId)->getHero();

                //opponent health
                $oppHealth = $combat->getOppHealth();

                //players health
                $playerHealth = $combat->getOwnerHealth();


                //event
                $combatEvent = $this->gameHistoryService->combatEvent($timestamp, $text, $playerBoard , $secondBoard , $hand = null, $winner, $damage, $opponent, $oppHealth, $playerHealth, $damageArray = null);

                $round->addEvent($combatEvent);
            
            }
        }
    }

    /**
     * Function that observe updating of Entity properties (AKA tags). Some of this changes are triggers for specific event or affect other objects.
     *
     * @param Entity $entity | property owner
     * @param String $tag | name of property
     * @param String $value | value of property
     * @param String $timestamp
     * @return void
     */
    private function observeTagUpdate($entity, $tag, $value, $timestamp)
    {
        //declaring game OWNER and 1st player
        if ($entity->getName() == 'GameEntity' && $tag === 'OWNER' ) {
            
            $array = $this->entityContainerByName['Player'];
            foreach ($array as $player) {
                if ($player->getName() == $value) {                    
                    $owner = new Player();
                    $owner->setName($value);
                    $owner->setPlayerId($player->getTagValueFromPairArray('PLAYER_ID'));
                    $owner->setStreak($this->file);
                    $this->game->setOwner($owner);
                    $this->game->addPlayer($owner);
                }
            }
        }

        //ADDING PLAYERS TO GAME
        if ($tag == 'HERO_POWER_ENTITY') {
            $entityId = $entity->getId();
            $playerId = $entity->getTagValueFromPairArray('PLAYER_ID');
            $playerArray = $this->game->getPlayers();
            if (
                !$this->game->getPlayerById($playerId)
                && count($this->game->getPlayers()) <= 8
                && $entity->getName() != 'FULL_ENTITY'
                && $value != 0
            ) {
                $hpEntity = $this->entityContainer[$value];
                $player = new Player();

                $heroPower = new HeroPower();

                $heroPower->setName($hpEntity->getName());
                $heroPower->setCardId($hpEntity->getCardId());
                if($hpEntity->getTagValueFromPairArray('COST')) {
                    $heroPower->setCost($hpEntity->getTagValueFromPairArray('COST'));
                }

                $player->setHero($entity->getCardId());

                $player->setHeroPower($heroPower);
                $this->game->updateHeroPower($heroPower->getCardId(), $playerId);
                $player->setPlayerId($entity->getTagValueFromPairArray('PLAYER_ID'));

                $player->setName($entity->getName());

                $this->game->addPlayer($player);

                if ($entity->getTagValueFromPairArray('PLAYER_LEADERBOARD_PLACE')) {
                    $this->game->addToLeaderboard($playerId, $entity->getTagValueFromPairArray('PLAYER_LEADERBOARD_PLACE'));
                }
                

                if(count($this->game->getPlayers()) === 8) {
                $event = $this->gameHistoryService->historyEvent('GAME',$timestamp, 'ALLOPP') ;
                $this->addGameEvent($event);               
                }

            } elseif ( $this->game->getPlayerById($playerId)
                && count($this->game->getPlayers()) >= 8
                && $entity->getName() != 'FULL_ENTITY'
                && $value != 0) {

                    $hpEntity = $this->entityContainer[$value];
                    $hpId = $hpEntity->getCardId();

                    $this->game->updateHeroPower($hpId, $playerId);
            }
        }
        
        //find id of the next opponent this round
        if($tag == 'NEXT_OPPONENT_PLAYER_ID') 
        {
            if ($this->game->getOwner() !=null) {
                if(!($entity->getName() == $this->game->getOwner()->getName())) {
                    foreach ($this->game->getPlayers() as $player) {
                        if($player->getPlayerId() == $value) {
                            $nextOpp = $player;
                        }
                    }
                    if(isset($nextOpp) && $nextOpp != null) {
                        $this->previousOpponent = $nextOpp;
                        $this->game->getcurrentRoundNrObject()->setNextOpponent($nextOpp);
                    } 
                }              
            }
        }

        //
        if ($tag == 'PLAYER_LEADERBOARD_PLACE') {

            $playerId = $entity->getTagValueFromPairArray('PLAYER_ID');

            if($this->game->getPlayerById($playerId)) {
                $this->game->addToLeaderboard($playerId, $value);
            }

        }

        if($tag == 'CARDTYPE') {

            if($value == 'MINION' || $value == 'SPELL' || $value == 'HERO_POWER' || $value == 'HERO') {
                $this->game->getCurrentRoundNrObject()->addMinion($entity);

                if(!$this->game->findCardInArray($entity->getId())) {
                   
                    $newCard = new Card();

                    $newCard->setType($value);
                    $newCard->setId($entity->getId());
                    $newCard->setCardId($entity->getCardId());
                    
                    if ($entity->getName() != null 
                        && $entity->getName() != 'FULL_ENTITY'
                        ) {
                        $newCard->setName($entity->getName());
                    }

                    $this->game->addCardToArray($newCard);
                } 

            }
        }

        if ($tag == 'PREMIUM') {

            if($this->game->findCardInArray($entity->getId()))
            {
                if ($value == 1) {
                    $this->game->findCardInArray($entity->getId())->setGolden(true);
                } else {
                    $this->game->findCardInArray($entity->getId())->setGolden(false);
                }
            }
        }
        if ($tag == 'BACON_TRIPLE_CANDIDATE') {
            if ($value == 1) {
                $this->game->findCardInArray($entity->getId())->setTripleCheck(true);
            } else {
                $this->game->findCardInArray($entity->getId())->setTripleCheck(false);
            }
        }

        if ($tag == 'DEATHRATTLE') {

            if ($value == 1) {
                $this->game->findCardInArray($entity->getId())->isDeathrattle(true);
            } else {
                $this->game->findCardInArray($entity->getId())->isDeathrattle(false);
            }
        }

        if ($tag == 'REBORN') {
            
            if ($value == 1) {
                $this->game->findCardInArray($entity->getId())->isReborn(true);
            } else {
                $this->game->findCardInArray($entity->getId())->isReborn(false);
            }

        }

        if ($tag == 'TRIGGER_VISUAL') {

            if (!($entity->getTagValueFromPairArray('CARDTYPE') == 'ENCHANTMENT')) {
                if ($value == 1) {
                    $this->game->findCardInArray($entity->getId())->isTrigger(true);
                } else {
                    $this->game->findCardInArray($entity->getId())->isTrigger(false);
                }
            }
        }

        
        if ($tag == 'POISONOUS') {

            if ($value == 1) {
                $this->game->findCardInArray($entity->getId())->isPoisonous(true);
            } else {
                $this->game->findCardInArray($entity->getId())->isPoisonous(false);
            }
        }

        if ($tag == 'TAUNT') {

            if ($value == 1) {
                $this->game->findCardInArray($entity->getId())->isTaunt(true);
            } else {
                $this->game->findCardInArray($entity->getId())->isTaunt(false);
            }

        }
        
        if ($tag == 'FROZEN') {

            if ($value == 1) {
                $this->game->findCardInArray($entity->getId())->isFrozen(true);
            } else {
                $this->game->findCardInArray($entity->getId())->isFrozen(false);
            }
        }

        if ($tag == 'DIVINE_SHIELD') {
            if ($this->game->findCardInArray($entity->getId())) {
                if ($value == 1) {
                    $this->game->findCardInArray($entity->getId())->isDivineShield(true);
                } else {
                    $this->game->findCardInArray($entity->getId())->isDivineShield(false);
                }
            }
        }

        if ($tag == 'RARITY') {
            if ($value == 'LEGENDARY') {
                $this->game->findCardInArray($entity->getId())->isLegendary(true);
            } else {
                $this->game->findCardInArray($entity->getId())->isLegendary(false);
            }
        }

        if ($tag == 'TECH_LEVEL') {
            $this->game->findCardInArray($entity->getId())->setTechLvl($value);
        }

        if ($tag == 'RESOURCES') {
            $ownerId = $this->game->getOwner()->getPlayerId();
            if ($entity->getTagValueFromPairArray('CARDTYPE') == 'PLAYER'
            && $entity->getTagValueFromPairArray('PLAYER_ID')
            && $entity->getTagValueFromPairArray('PLAYER_ID') == $ownerId
            ) {
                $this->game->setResources($value);
            }

        }

        if ($tag == 'TEMP_RESOURCES') {

            $ownerId = $this->game->getOwner()->getPlayerId();
            if ($entity->getTagValueFromPairArray('CARDTYPE') == 'PLAYER'
            && $entity->getTagValueFromPairArray('PLAYER_ID')
            && $entity->getTagValueFromPairArray('PLAYER_ID') == $ownerId
            ) {
                $this->game->setTempResources($value);
            }
        }

        if ($tag == 'RESOURCES_USED') {
            $ownerId = $this->game->getOwner()->getPlayerId();
            
            if ($entity->getTagValueFromPairArray('CARDTYPE') == 'PLAYER'
            && $entity->getTagValueFromPairArray('PLAYER_ID')
            && $entity->getTagValueFromPairArray('PLAYER_ID') == $ownerId
            ) {
                $this->game->setUsedResources($value);
            }
        }

        if ($tag == 'DAMAGE') {
            
            $ownerId = $this->game->getOwner()->getPlayerId();
            if($entity->getTagValueFromPairArray('CARDTYPE') == 'HERO'
            && $entity->getTagValueFromPairArray('PLAYER_ID') 
            && $entity->getTagValueFromPairArray('PLAYER_ID') != $ownerId + 9
            && $entity->getTagValueFromPairArray('PLAYER_ID') != $ownerId
            ) {
                if( $this->game->getPlayerById($entity->getTagValueFromPairArray('PLAYER_ID')))
                { 
                    $this->game->getPlayerById($entity->getTagValueFromPairArray('PLAYER_ID'))->setHealth(40 - $value);
                } else {
                    array_push($this->disconected, $entity->getTagValueFromPairArray('PLAYER_ID') );
                    
                }
            }
        }

        if ($tag == '1429') {
            $card = $this->game->findCardInArray($entity->getId());
            $card->setDbId($value);
        }

        if ($tag == 'CARDRACE') {

            $card = $this->game->findCardInArray($entity->getId());

            $card->setTribe($value);

            if (count($this->game->getTribes()) < 5
                && !$this->game->hasTribe($value)
                && $value != 'PET'
                && $value != 'ALL') {
                    $this->game->addTribe($value);
            }
        }

        if ($tag == 'PLAYER_TECH_LEVEL') {
            if ($entity->getTagValueFromPairArray('PLAYER_ID')
                && $this->game->getPlayerById($entity->getTagValueFromPairArray('PLAYER_ID'))
            ) {
                $playerId = $entity->getTagValueFromPairArray('PLAYER_ID');

                if ($playerId != $this->game->getOwner()->getPlayerId()
                    && $value > 1
                    && $value > $this->game->getPlayerById($playerId)->getTavernLvl()
                ) {
                    
                    $gameEvent = new Event;
                    $gameEvent = $this->gameHistoryService->historyEvent(
                        'GAME',  //event type
                        $timestamp, //timestamp 
                        'UPGRADE' , //text\content
                        null, //owner board
                        null, //second board - mulligan options
                        null,  //hand - mulligan pick
                        $playerId, //gold - upgrading player ID
                        $value, //tavern lvl 
                        null, //reroll cost 
                        null  //tavern upgrade cost 
                    );
                    $this->addGameEvent($gameEvent);
                }

                $this->game->getPlayerById($playerId)->setTavernLvL($value);
            }

        }

        if ($tag == 'PLAYER_TRIPLES') {

            if ($entity->getTagValueFromPairArray('PLAYER_ID')
                && $this->game->getPlayerById($entity->getTagValueFromPairArray('PLAYER_ID'))
            ) {
                $playerId = $entity->getTagValueFromPairArray('PLAYER_ID');

                if ($playerId != $this->game->getOwner()->getPlayerId()
                    && $value > $this->game->getPlayerById($playerId)->getTriples()
                ) {
                    $gameEvent = new Event;
                    $gameEvent = $this->gameHistoryService->historyEvent(
                        'GAME',  //event type
                        $timestamp, //timestamp 
                        'TRIPLE' , //text\content
                        null, //owner board
                        null, //second board - mulligan options
                        null,  //hand - mulligan pick
                        $playerId, //gold - upgrading player ID
                        $value, //tavern lvl 
                        null, //reroll cost 
                        null  //tavern upgrade cost 
                    );
                    
                    $this->addGameEvent($gameEvent);
                }
                $this->game->getPlayerById($playerId)->setTriples($value);
            }
        }

        if ($tag == 'COST') {

            $entityCardId = $entity->getCardId();

            //REROLL COST
            if ($entityCardId === 'TB_BaconShop_8p_Reroll_Button') {             
                $this->game->setRerollCost($value);
            }

            //UPGRADE COST
            if ($this->game->getOwner()) {
                $upLvl = $this->game->getOwner()->getTavernLvl()+1;
                $upgradeButton = 'TB_BaconShopTechUp0'. $upLvl .'_Button';
                
                if ($entityCardId == $upgradeButton) {
                    $this->game->setTavernUpgradeCost($value);
                }
            }
        }

        if ($tag == 'CREATOR') {
            
            if ($this->game->getOwner()
                && $this->game->getOwner()->getHeroPower() == null
                ) {

                    if ($entity->getTagValueFromPairArray('CARDTYPE') == 'HERO_POWER'
                        && isset($this->entityContainer[$value])
                        && $this->entityContainer[$value]->getTagValueFromPairArray('PLAYER_ID') == $this->game->getOwner()->getPlayerId()
                        && $entity->getName() != 'FULL_ENTITY'
                    ) {
                        $heroPower = new HeroPower();

                        $heroPower->setName($entity->getName());
                        $heroPower->setCardId($entity->getCardId());
                        if($entity->getTagValueFromPairArray('COST')) {
                            $heroPower->setCost($entity->getTagValueFromPairArray('COST'));
                        }
                        $this->game->getOwner()->setHeroPower($heroPower);
                        $this->game->updateHeroPower($heroPower->getCardId(), $this->game->getOwner()->getPlayerId());
                }
            }
        }

        //DODAJE KARTĘ DO RĘKI
        if($tag == 'ZONE') {
            if($value == 'HAND' 
            && ($entity->getTagValueFromPairArray('CARDTYPE') == 'SPELL' || $entity->getTagValueFromPairArray('CARDTYPE') == 'MINION' )
            && !(isset($this->mergedCards[$entity->getId()]))
            && $entity->getTagValueFromPairArray('CONTROLLER') == $this->game->getOwner()->getPlayerId()
            ) {
                if ($this->game->findCardInArray($entity->getId()) 
                && !$this->game->getOwner()->findCardInHand($entity->getId())
                ) {
                    $card = $this->game->findCardInArray($entity->getId());

                    $newSpell = clone $card;

                    $this->game->getOwner()->addToHand($newSpell);
                }
            }
        }

        if($value == 'FINAL_GAMEOVER' && $tag == 'NEXT_STEP' && $this->lastRoundSumarized == false) {

            $this->lastRoundSumarized = true;
            $this->game->createLastRoundLeaderboard();
        }
    }

    /**
     * Updates property of card
     *
     * @param Int $id | ID of specific card
     * @param String $tag | property
     * @param String $value 
     * @return void
     */
    public function updateCard($id, $tag, $value)
    {
        $card = $this->game->findCardInArray($id);

        if ($tag == "HEALTH") {
            $card->setHealth($value);
        }

        if ($tag == "ATK") {
            $card->setAttack($value);
        }

        if ($tag == "NAME") {
            $card->setName($value);
        }

        if ($card->getType() == 'MINION' || $card->getType() == 'SPELL') {
            $this->game->checkUpdateBoards($card);
        }
       
    }

    /**
     * Adds game events and player moves to Round and Game object
     *
     * @param Event $event
     * @return void
     */
    public function addGameEvent($event)
    {
        $this->game->getCurrentRoundNrObject()->addAction($event);
        $this->game->addGameEvent($event);     
    }

    /**
     * Update boards and player hand after player make a move
     *
     * @return void
     */
    public function parseCurrentOptionBlock()
    {
        $tempShop = $this->game->getInnkeeper()->getBoard();

        foreach ($this->game->getCurrentOptionBlock()->getOptions() as $option) {

            if($option->getMainEntity()->getCardId() === 'TB_BaconShop_DragSell' && $option->getTargets() != null) {
                
                $this->game->getOwner()->clearBoard();
                $this->game->getInnkeeper()->clearBoard();

                foreach ($option->getTargets() as $target) {

                    if ($target->getMainEntity()->getTagValueFromPairArray('CARDTYPE') == 'MINION' 
                    && $this->game->findCardInArray($target->getMainEntity()->getId())
                    ) {
                        if ($target->getPlayerId() == $this->game->getOwner()->getPlayerId())
                        {   
                            if ($this->nextActionPosition != 0 
                            && $target->getMainEntity()->getId() == $this->nextActionTarget->getId()
                            ) {
                                $this->game->findCardInArray($target->getMainEntity()->getId())->setPosition($this->nextActionPosition);
                            } else {
                                $this->game->findCardInArray($target->getMainEntity()->getId())->setPosition($target->getZonePosition());
                            }
                            $this->game->getOwner()->addToBoard($this->game->findCardInArray($target->getMainEntity()->getId()));

                        } else {
                            $this->game->findCardInArray($target->getMainEntity()->getId())->setPosition($target->getZonePosition());
                            $this->game->getInnkeeper()->addToBoard($this->game->findCardInArray($target->getMainEntity()->getId()));
                        }
                    }   
                }

            //clear board after selling last minion
            } elseif ($option->getMainEntity()->getCardId() === 'TB_BaconShop_DragSell' 
            && $option->getTargets() == null
            && $this->nextAction == 'SOLD'
            ) {
                $this->game->getOwner()->clearBoard();
            }

            if($option->getMainEntity()->getCardId() === 'TB_BaconShop_DragBuy' && $option->getTargets() != null
            ) {
                $this->game->getInnkeeper()->clearBoard();

                foreach ($option->getTargets() as $target) {

                    if ($target->getMainEntity()->getTagValueFromPairArray('CARDTYPE') == 'MINION' 
                    && $target->getPlayerId() == $this->game->getInnkeeper()->getPlayerId()
                    && $this->game->findCardInArray($target->getMainEntity()->getId())
                    ) {
                        $this->game->findCardInArray($target->getMainEntity()->getId())->setPosition($target->getZonePosition());
                        $this->game->getInnkeeper()->addToBoard($this->game->findCardInArray($target->getMainEntity()->getId()));
                    }
                }
            }
        }
    }

    /**
     * Adding golden card after finding third copy
     *
     * @param Event $event
     * @return void
     */
    private function manageGolden($event)
    {
        $content = $event->getContent();
        
        parse_str($content, $variable);

        if(isset($variable['entity'])) {
            $id = $variable['entity'];

            if ($this->game->findCardInArray($id) != null) {
                $this->game->getOwner()->addToHand($this->game->findCardInArray($id));
            }
        }
    }

    /**
     * Removing all copies of trippled card from boards and players hand
     *
     * @param Event $event
     * @return void
     */
    private function manageTripleMerge($event)
    {
        $content = $event->getContent();

        parse_str($content, $variable);

        if(isset($variable['entity'])) {
            $id = $variable['entity'];

            if ($this->game->findCardInArray($id) != null) {

                $this->mergedCards[$id] = $this->game->findCardInArray($id);

                if($this->game->getOwner()->findCardInHand($id)) {   

                    $this->game->getOwner()->removeCardFromHand($this->game->findCardInArray($id));
                }
            }            
        }
    }

    /**
     * Processes boards of both players entering "Combat Phase"
     *
     * @param Event $event
     * @return void
     */
    private function manageCombat($event)
    {   
        
        if ($this->game->getCurrentRoundNr() > 0) {
            
            $content = $event->getContent();
        
            parse_str($content, $variable);

            if(isset($variable['cbtId'])) {

                if($this->game->getCombatById($variable['cbtId']) != null) {

                    if (isset($variable['winner'])) {

                        $winnerId = $this->entityContainer[$variable['winner']]->getTagValueFromPairArray('PLAYER_ID');
                        
                        if($winnerId == $this->game->getOwner()->getPlayerId()) {
                            $winner = 1;
                        } else {
                            $winner = -1;
                        }
                        $this->game->getCombatById($variable['cbtId'])->setWinner($winner);
                    }
        
                    elseif (isset($variable['damage'])) {
                        $attacker = new Option();
                        $attacker->setMainEntity($variable['entity']);
                        $attacker->setZonePosition($variable['zonePos']);
                        $attacker->setPlayerId($variable['player']);
                        $this->game->getCombatById($variable['cbtId'])->addDamage($variable['damage']);
                        $this->game->getCombatById($variable['cbtId'])->addTarget($attacker);
                    }
        
                    elseif(isset($variable['all'])) {

                        if($this->entityContainer[$variable['entity']]->getTagValueFromPairArray('CARDTYPE') == 'MINION' 
                        && $this->game->findCardInArray($variable['entity'])) {

                            $copied = $this->game->findCardInArray($variable['entity']);
                            $minion = clone $copied;
                            $minion->setPlayerId($variable['player']);
                            $minion->setPosition($variable['zonePos']);
                        
                            $this->game->getCombatById($variable['cbtId'])->addMinion($minion);    
                        }

                    }  elseif (isset($variable['name'])) {
                        
                        $this->game->getCombatById($variable['cbtId'])->setOppName($variable['name']);

                        if (is_numeric($variable['oppId'])) {
                            $this->game->getCombatById($variable['cbtId'])->setOppPlayerId($variable['oppId']);
                                                        
                            if($this->game->getRoundByNr($variable['cbtId']) != NULL
                            && $this->game->getRoundByNr($variable['cbtId'])->getNextOpponent() == null) {
                                
                                $this->game->getRoundByNr($variable['cbtId'])->setNextOpponent($this->game->getPlayerById($variable['oppId']));
                                $this->previousOpponent = $this->game->getPlayerById($variable['oppId']);

                            }

                        }
        
                    } elseif(isset($variable['board'])) {
                        
                        if($nextOppId = $this->game->getRoundByNr($variable['cbtId'])->getNextOpponent() == null) {

                        } else {
                            $nextOppId = $this->game->getRoundByNr($variable['cbtId'])->getNextOpponent()->getPlayerId();
                        }


                        
                        
                        $this->game->getCombatById($variable['cbtId'])->setOppPlayerId($nextOppId);

                        $oppHp = $this->game->getPlayerById($nextOppId)->getHealth();
                        
                        $this->game->getCombatById($variable['cbtId'])->setOppHealth($oppHp);

                        $ownerHp = $this->game->getOwner()->getHealth();

                        $this->game->getCombatById($variable['cbtId'])->setOwnerHealth($ownerHp);

                        if($this->entityContainer[$variable['entity']]->getTagValueFromPairArray('CARDTYPE') == 'MINION' && $this->game->findCardInArray($variable['entity'])) {

                            $copiedCard = $this->game->findCardInArray($variable['entity']);

                            $boardMinion = clone $copiedCard;

                            if ($variable['player'] == $this->game->getOwner()->getPlayerId()) {                                
                                $this->game->getCombatById($variable['cbtId'])->addToPlayerBoard($boardMinion);           
                            } else {            
                                $this->game->getCombatById($variable['cbtId'])->addToOppBoard($boardMinion);            
                            }
                        }     
                    }

                } else {
                    if ($this->game->getCombatById($variable['cbtId'] - 1 ) != null) {
                        $this->game->getCombatById($variable['cbtId'] - 1 )->checkBoard();
                    }
                    
                    $newCombat = new Combat();
                    $newCombat->setOwnerPlayerId($this->game->getOwner()->getPlayerId());
                    $newCombat->setId($variable['cbtId']);
                    if (isset($variable['winner'])) {                        
                        $newCombat->setWinner($variable['winner']);
                    }
                    if (isset($variable['name'])) {
                        $newCombat->setOppName($variable['name']);
                    } elseif(isset($variable['board'])) {
                        
                        $boardMinion = new Option();
                        $boardMinion->setMainEntity($this->entityContainer[$variable['entity']]);
                        $boardMinion->setZonePosition($variable['zonePos']);
                        $boardMinion->setPlayerId($variable['player']);
                        $boardMinion->setBlockId($variable['entity']);
                        
                        if($this->entityContainer[$variable['entity']]->getTagValueFromPairArray('CARDTYPE') == 'MINION')
                        
                        if ($variable['player'] == $this->game->getOwner()->getPlayerId()) {                            
                            $this->game->getCombatById($variable['cbtId'])->addToPlayerBoard($boardMinion);        
                        } else {
                            $this->game->getCombatById($variable['cbtId'])->addToOppBoard($boardMinion);       
                        }                          
                    }
                    
                    $newCombat->setMainEntity($event->getTimestamp());

                    if ($newCombat->getOppPlayerId() == null) {
                        $this->game->getRoundByNr($variable['cbtId'])->setNextOpponent($this->previousOpponent);
                    }
                    
                    $this->game->addCombat($newCombat);
                }
            }
        }    
    }

    /**
     * Handles combat damage
     *
     * @param Event $event
     * @return void
     */
    private function manageDamage($event)
    {
        $content = $event->getContent();
        parse_str($content, $variable);
        $id = $variable['entity'];

        $entity = $this->entityContainer[$id];
        
        if ($this->game->getOwner()) {
            $ownerId = $this->game->getOwner()->getPlayerId();

            if($entity->getTagValueFromPairArray('CARDTYPE') == 'HERO'
            && $entity->getTagValueFromPairArray('PLAYER_ID')
            && $entity->getTagValueFromPairArray('PLAYER_ID') == $ownerId
            ) {

                $currenthealth = $this->game->getOwner()->getHealth();
                $this->game->getOwner()->setHealth($currenthealth - $variable['damage']);
                
            }
        }
    }
    
    /**
     * Returns round by their nuber
     *
     * @param int $roundNumber
     * @return Round
     */
    public function getRoundByNumber($roundNumber)
    {
        return $this->game->getRoundByNr($roundNumber);
    }

    /**
     * Adds all events that happend before first round
     *
     * @return void
     */
    private function addStartEvents()
    {
            $raoundZero = $this->game->getRoundByNr(0);
            $raoundOne = $this->game->getRoundByNr(1);

            $eventArray = $raoundZero->getEvents();
            $lastIndex = count($eventArray) - 1;

        for($i = $lastIndex ;  $i>=0; $i--) { 

            $this->game->getRoundByNr(1)->addZeroEvents($eventArray[$i]);

        }
    }
}