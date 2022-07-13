<?php

namespace App\Service\LogCrawler;


use App\Service\Parser;
use App\Service\LogCrawler\Entities\Entity;
use App\Service\EventCollector\EventCollector;
use App\Service\EventInterpreter\Entity\Option;
use App\Service\EventInterpreter\Entity\OptionBlock;
use App\Service\EventInterpreter\Entity\Combat;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * This service is crawling through log file and finds all importand changes in data.
 * Data are saved as Events that will be pass to EventInterpreter
 */
class Crawler
{
    private $entityArray;

    private $parsedLogs;

    private $parsedFile;

    private $logTablePosition;

    private $eventsArray = array();

    private $logFileLength;

    private $entityContainerById  = array();

    private $entityContainerByName  = array();

    private $opponentBoardArray = array();

    private $currentCombat;

    private $currentCombatNr = 0;

    private $tempArray = array();

    private $testArray = array();

    private $gameFlag = false;

    private $cbtNameCheck = false;

    private $cbtAllCheck = false;

    private $params;

    /**
     * Adds entity to array that contains all captured entities where the key is Entity ID
     *
     * @param Entity $value
     * @return void
     */
    public function addEntitytById(Entity $value)
    {
        $id = $value->getId();
        $this->entityContainerById[$id] = $value;
    }

    /**
     * Adds entity to array that contains all captured entities where the key is Entity ID
     *
     * @param Entity $value
     * @return void
     */
    public function addEntitytByName(Entity $value)
    {
        $name = $value->getName();
        $id = $value->getId();

        $this->entityContainerByName[$name][$id] = $value;
    }

    /**
     * Updates entity in container
     *
     * @param Entity $value
     * @param $oldName
     * @return void
     */
    public function updateEntitytByName(Entity $value, $oldName)
    {
        $id = $value->getId();
        $name = $value->getName();
        if (isset($this->entityContainerByName[$oldName][$id])) {
            unset($this->entityContainerByName[$oldName][$id]) ;
        }
        $this->entityContainerByName[$name][$id] = $value;
    }

    /**
     * Returns number of current row of data that is analyzed
     *
     * @return Int
     */
    public function getLogTablePosition()
    {
        return $this->logTablePosition;
    }

    /**
     * Returns table of entities sorted by ID
     *
     * @return array()
     */
    public function getEntityContainerById()
    {
        return $this->entityContainerById;
    }

    /**
     * Returns table of entities sorted by name
     *
     * @return array()
     */
    public function getEntityContainerByName()
    {
        return $this->entityContainerByName;
    }

    /**
     * Returns entity selected by ID
     *
     * @param $id
     * @return Entity
     */
    private function getEntityFromContainerById($id) 
    {
        return $this->entityContainerById[$id];
    }


    /**
     * Function that reserts data, trigger parsing file and initialize new game
     *
     * @param $fileName | name of file
     * @param $path | path to file
     * @return void
     */
    public function crawlThroughFile($fileName, $path)
    {
        $this->eventCollector = new EventCollector();
        $parser = new Parser();
        
        $this->parsedLogs = $parser->parseFile($fileName, $path);
        $this->logFileLength = count($this->parsedLogs);
        
        $this->currentCombatNr = 0;
        $this->logTablePosition = 0;
        $table = $this->parsedLogs;
        $position = $this->getLogTablePosition();
        $finish = $this->logFileLength;
        
        $this->initializeGame();

        
    }

    /**
     * Main funtion that interprete data from file and change them from string to PHP variables
     *
     * @return void
     */
    public function initializeGame()
    {
        $table = $this->parsedLogs;
        $position = $this->getLogTablePosition();
        $row = $table[$position];
        
        if (strstr($row['content'], 'CREATE_GAME') == 0 ){
            // event table    
            $event['timestamp'] = $row['timestamp'];
            $event['event'] = 'GAME STARTED!';

            $proceed = true;
                
            while ($position < $this->logFileLength-2) {
                $position++;
                $row = $this->parsedLogs[$position];

                if (!(strpos($row['content'], 'PlayerID') === 0)
                && (strpos($row['content'], 'GameEntity') === 0 
                    || strpos($row['content'], 'Player') === 0
                    || strpos($row['content'], 'FULL_ENTITY') === 0
                    || strpos($row['content'], 'SHOW_ENTITY') === 0
                    )
                ) 
                {
                    $proceed = $this->manageEntity($position);
                } elseif (strpos($row['function'], 'PowerTaskList.DebugPrintPower()') === 0
                    && strpos($row['content'], 'value=COMPLETE') 
                ) {
                    $this->eventCollector->addGameFinish($row['timestamp']);

                } elseif(strpos($row['content'], 'TAG_CHANGE') === 0) {
                    $proceed = $this->manageTagChange($position);
            
                } elseif(strpos($row['content'], 'GameType') === 0 ) {
                    
                    $gameEntityId = $this->findEntityIdByName('GameEntity');
                    
                    if (isset($this->entityContainerById[$gameEntityId])) {
                        $this->entityContainerById[$gameEntityId]->setTag('GAME_TYPE');
                        $findType = $this->parseValue($row['content']);
                        $this->entityContainerById[$gameEntityId]->setTagValue($findType[1]);
                        $this->entityContainerById[$gameEntityId]->updatePairInArray();   

                        $this->eventCollector->updateTag($row['timestamp'], $gameEntityId, 'GAME_TYPE', $findType[1]);
                    }

                } elseif (strpos($row['content'], 'PlayerID') === 0) {
                    $rows = explode(",", $row['content']);

                    $playerId = $this->parseValue($rows[0]);
                    unset($rows[0]);

                    $playerId = $playerId[1];

                    foreach ($this->entityContainerByName['Player'] as $key => $value) {
                        
                        if ($value->getTagValueFromPairArray("PLAYER_ID") == $playerId) {
                            
                            if (strpos($row['content'], 'PlayerName')) {
                                $start = strpos($row['content'], 'PlayerName');
                                $subStr = substr($row['content'],$start);
                                $newName = $this->parseValue($subStr);
                                $newName = $newName[1];
                                $value->setName($newName);

                                $playerEntityId =  $this->findEntityIdByName('GameEntity');
                                
                                $this->eventCollector->updateEntity($row['timestamp'], $key, $newName, null);
                                
                                $this->updateEntitytByName($value, 'Player');

                                if ($newName != 'The Innkeeper') {
                                    $gameEntityId = $this->findEntityIdByName('GameEntity');

                                    $this->entityContainerById[$gameEntityId]->updateTagInArray('OWNER', $newName);
                                    $this->eventCollector->updateTag($row['timestamp'], $gameEntityId, 'OWNER', $newName);                                   
                                }
                            }                            
                        }                        
                    }
                } elseif (strpos($row['function'], 'GameState.DebugPrintEntityChoices()') === 0) {
                    
                    $position = $this->manageMulligan($position) -1;

                } elseif (strpos($row['function'], 'GameState.SendChoices()') === 0) {
                    
                    $position =  $this->manageSendChoice($position);

                } elseif (strpos($row['function'], 'GameState.DebugPrintOptions()') === 0) {
                    
                    $position =  $this->manageOptions($position);
                    
                } elseif (strpos($row['function'], 'GameState.SendOption()') === 0) {

                    $this->manageSelectOption($position);
                     
                } elseif (strpos($row['content'], 'SUB_SPELL_START - SpellPrefabGUID=Bacon_TripleMerge_OverrideSpawn_Super') === 0 ) {
                    ($position);
                    $position = $this->manageOwnerTriple($position);

                } elseif (
                    strpos($row['content'], 'SUB_SPELL_START - SpellPrefabGUID=Bacon_TripleMerge_Impact_MergeMinions_Super') === 0 
                    && $row['function'] ==  'GameState.DebugPrintPower()') {
                    ($position);
                    $position = $this->manageTripleMerge($position);

                } elseif ( 
                    strpos($row['content'], 'SUB_SPELL_START - SpellPrefabGUID=Bacon_EndRound_BuffHeroAttack_Super') === 0 
                    && $row['function'] ==  'GameState.DebugPrintPower()'
                    ) {
                        ($position);
                        $position = $this->manageCombatReasult($position);

                } elseif (strpos($row['content'], 'BLOCK_START') === 0) {
                    $this->manageBlock($position);
                } elseif (strpos($row['content'], 'META_DATA - Meta=DAMAGE') === 0 
                && $row['function'] ==  'GameState.DebugPrintPower()'
                ) {
                    $this->manageCombatDamage($position);
                }
            }
        }

        $this->entityContainerByName = array();
        $this->entityContainerById = array();
    }

    /**
     * Function that handles creating entities and changing their parameters
     *
     * @param $position
     * @return void
     */
    public function manageEntity($position)
    {
        $table = $this->parsedLogs;
        $row = $table[$position];

        $array = explode(" ", $row['content']);

        $header = $this->decodeEntityHeader($row['content']);

        if (isset($header['id'])) {
            $id = $header['id'];
        }

        if (isset($this->entityContainerById[$id])) {
            $entity = $this->getEntityFromContainerById($id);
            //event table
            $event['timestamp'] = $row['timestamp'];
            
            if ($header['name'] && (($entity->getName()) == null || $entity->getName() === 'FULL_ENTITY' )) {
                //update property name name and create NAME tag
                $oldName = $entity->getName();
                $name = $header['name'];
                $entity->setName($name);
                $entity->setTag('NAME');
                $entity->setTagValue($name);
                $entity->updatePairInArray();
                $this->updateEntitytByName($entity, $oldName);
            } else {
                $name = null;
            }
    
            if (isset($header['cardId']) && ($entity->getCardId()) == null ) {
                //update property cardID and create CARD_ID tag
                $cardId = $header['cardId'];
                $entity->setCardId($cardId);
                $entity->setTag('CARD_ID');
                $entity->setTagValue($cardId);
                $entity->updatePairInArray();
            } else {
                $cardId = null;
            }
            
            $this->eventCollector->updateEntity($event['timestamp'], $id, $name, $cardId);
        } else {

            $entity = new Entity();

            $name =$array[0];

            $entity->setName($array[0]);

            $entity->setId($id);

            $this->addEntitytByName($entity);
    
            //event table
            $event['timestamp'] = $row['timestamp'];
            
            if ($header['name']) {
                //update property name name and create NAME tag
                $name = $header['name'][1];
                $entity->setName($name);
                $entity->setTag('NAME');
                $entity->setTagValue($name);
                $entity->updatePairInArray();
            } else {
                $name = $name;
            }
    
            if (isset($header['cardId'])) {
                //update property cardID and create CARD_ID tag
                $cardId = $header['cardId'];
                $entity->setCardId($cardId);
                $entity->setTag('CARD_ID');
                $entity->setTagValue($cardId);
                $entity->updatePairInArray();
            } else {
                $cardId = null;
            }

            if($name == 'GameEntity' && $this->gameFlag == false) {
                $this->eventCollector->createEntity($event['timestamp'], $id, $name, $cardId);
                $this->gameFlag = true;
            } elseif($name != 'GameEntity') {
                $this->eventCollector->createEntity($event['timestamp'], $id, $name, $cardId);
            }
        }

        while ( substr($table[$position+1]['content'], 0, 3) == "tag" && $position < count($table)-2) {
            
            $position++;

            $row = $table[$position];

            $array = explode(" ", $row['content']);

            $getTag = $this->parseValue($array[0]);
            $tagName = $getTag[1];

            $getVar = $this->parseValue($array[1]);
            $varValue =  $getVar[1];

            $this->eventCollector->updateTag($row['timestamp'], $id, $tagName, $varValue);

            $entity->setTag($tagName);
            $entity->setTagValue($varValue);
            $entity->updatePairInArray();
        }

        $this->addEntitytById($entity);

        return false;
    }

    /**
     * Hndles TAG_CHANGE where value of property is changed
     *
     * @param $position
     * @return void|bool
     */
    public function manageTagChange($position)
    {
        $table = $this->parsedLogs;
        $row = $table[$position];

        $string = trim($row['content'], 'TAG_CHANGE');
        $string = trim($string, ' ');

        if(!(strpos($string, '['))){
            $array = explode(" ", $string);
            
            $entityPos = strpos($string, 'Entity');
            $tagPos = strpos($string, 'tag');
            $valuePos = strpos($string, 'value');

            $entityString = substr($string, $entityPos, $tagPos-1);
            $identifier = $this->parseValue($entityString);
            $identifier = $identifier[1];
            
            $gameEntityId = $this->findEntityIdByName('GameEntity');

            switch ($identifier) {
                case 'GameEntity':
                    $identifier = $gameEntityId;
                    break;
                case 'The Innkeeper':
                    $identifier = $this->findEntityIdByName('The Innkeeper');
                    break;    
                case $this->entityContainerById[$gameEntityId]->getTagValueFromPairArray('OWNER'):
                    $identifier = $this->findEntityIdByName($identifier);
                    break;
                case "Bob's Tavern":
                    $identifier = $this->findEntityIdByName($identifier);             
                    break; 
                default:
                    if(is_numeric($identifier)) {

                    } else {

                        if($this->findEntityIdByName($identifier) != null) {
                            $identifier = $this->findEntityIdByName($identifier);
                        }
                    }
            }

            $tagString = substr($string, $tagPos, $valuePos-$tagPos-1);
            $tagName = $this->parseValue($tagString);
            $tagName = $tagName[1];

            $valueString = substr($string, $valuePos);
            $tagValue = $this->parseValue($valueString);
            $tagValue = $tagValue[1];
                        
            if(isset($this->entityContainerById[$identifier]))
            {
                $this->entityContainerById[$identifier]->updateTagInArray($tagName, $tagValue);
                //event table
                $event['timestamp'] = $row['timestamp'];
                
            
            } elseif (!is_numeric($identifier) && $tagName = 'HERO_ENTITY') {
                $entity = new Entity();

                $name =$identifier;

                $entity->setName($identifier);

                $entity->setId($tagValue);

                $this->addEntitytByName($entity);
            }
           
            $this->eventCollector->updateTag($row['timestamp'], $identifier, $tagName, $tagValue);
            return false;
        } else {
            if (strpos($string, '[cardType=INVALID]')) {
                $start = strpos($string, '[cardType=INVALID]');
                $length = strlen('[cardType=INVALID]');
                $string = substr_replace($string, '',$start,$length +1);
            }

            if (strpos($string, '[DNT]')){               
                $start = strpos($string, '[DNT]');
                $length = strlen('[DNT]');
                $string = substr_replace($string, '',$start,$length +1);
            }

            $bracketStart = strpos($string, '[');
            $bracketEnd = strpos($string, ']');
            $length = $bracketEnd - $bracketStart - 1;

            $entityInfo = substr($string, $bracketStart + 1, $length);
            $updatedTag = substr($string, $bracketEnd + 1);
            $updatedTagText = trim($updatedTag , ' ');

            $entityIdArray = $this->decodeEntityHeader($entityInfo);

            if(isset($entityIdArray['id'])) {
                $identifier = $entityIdArray['id'];
            }

            $array = explode(" ", $updatedTagText);

            $tagName = $this->parseValue($array[0]);
            $tagName = $tagName[1];

            $tagValue = $this->parseValue($array[1]);
            $tagValue = $tagValue[1];                        

            if(isset($this->entityContainerById[$identifier])) {

                $this->entityContainerById[$identifier]->updateTagInArray($tagName, $tagValue);
                //event table
                $event['timestamp'] = $row['timestamp'];
                
                $this->eventCollector->updateTag($event['timestamp'], $identifier, $tagName, $tagValue);
            }

            return false;
        }
    }

    /**
     * Changes string into variables
     *
     * @param $string
     * @return array()
     */
    public function parseValue($string)
    {
        $eqlPosiotion = strpos($string, "=");

        if ($eqlPosiotion) {
            $variable = substr($string, 0,$eqlPosiotion);
            $value = substr($string, $eqlPosiotion+1);
    
            return [$variable, $value];
        } else {
            return [$string, $string];
        }   
    }

    /**
     * Updates property change in Entity container
     *
     * @param $array
     * @param $date
     * @return void
     */
    public function updateTagInEntityContainerById($array, $date)
    {
        $entityId = $this->parseValue($array[1]);
     
        $entityId = $entityId[1];
        
        if(isset($this->entityContainerById[$entityId])) {
            
            $tagName = $this->parseValue($array[2]);
            $tagValue = $this->parseValue($array[3]);
            
            $tempEntity = $this->entityContainerById[$entityId];
            
            $tempEntity->setTag($tagName[1]);
            $tempEntity->setTagValue($tagValue[1]);

            $tempEntity->updatePairInArray();

            return $this->entityContainerById;
        }
    }

    /**
     * Finds ID of entity by name
     *
     * @param $name
     * @return void
     */
    public function findEntityIdByName($name)
    {
        if (isset($this->entityContainerByName[$name])) {
            foreach ($this->entityContainerByName[$name] as $key => $value) {
                return $key;
            }
        }
    }
    
    /**
     * Returns entity ID if given
     *
     * @param $array
     * @return int|false 
     */
    public function findEntityIdInArray($array)
    {
        foreach ($array as $string) {

            if (strpos($string, 'EntityID') === 0 
            || strpos($string, 'ID') === 0 
            || strpos($string, 'id') === 0  
            && !(strpos($string, 'CardID'))
            ){
                $value = $this->parseValue($string);

                return $value[1];
            }
        }

        return null;
    }

    /**
     * Finds entity that is "card" in entity container
     *
     * @param $array
     * @return void
     */
    public function findCardIdInArray($array){
        
        foreach ($array as $string) {
            if ((strpos($string, 'CardID')) === 0) {
                $value = $this->parseValue($string);
                return $value[1];
            }
        }    
        return null;
    }

    /**
     * Returns entity name if given
     *
     * @param $string
     * @return String
     */
    public function findEntityName($string){
        if ((strpos($string, 'entityName')) != null && (strpos($string, 'entityName')) != false){
            $nameStart = strpos($string, 'entityName');

            $substr = substr($string, $nameStart);
            $idStart = strpos($substr, 'id=');

            $value = substr($substr, 0, $idStart -1);

            return $this->parseValue($value);
        }
    }

    /**
     * Reads entity header and updates its properties
     *
     * @param $string
     * @return Entity
     */
    public function decodeEntityHeader($string)
    {
        $name = $this->findEntityName($string);
        if (is_array($name)) {
            $entity['name']= $name[1];
        } else {
            $entity['name']= $name;
        }

        $array = explode(" ", $string);

        foreach ($array as $string) {

            if (strpos($string, 'EntityID') === 0 
            || strpos($string, 'ID') === 0 
            || strpos($string, 'id') === 0 
            || strpos($string, 'Entity') === 0 
            && !(strpos($string, 'CardID'))
            ){
                $value = $this->parseValue($string);

                $entity['id'] = $value[1];
            }

            if ((strpos($string, 'CardID')) === 0) {
                $value = $this->parseValue($string);
                $entity['cardId']= $value[1];
            }

            if ((strpos($string, 'player')) === 0) {
                $value = $this->parseValue($string);
                $trimedValue = rtrim($value[1], ']');
                $entity['playerId']= $trimedValue;
            }

            if ((strpos($string, 'zone')) === 0 && ! (strpos($string, 'zonePos') === 0 )) {
                $value = $this->parseValue($string);
               
                $entity['zone']= $value[1];
            }

            if ((strpos($string, 'zonePos')) === 0) {
                $value = $this->parseValue($string);
               
                $entity['zonePos']= $value[1];
            } 
        }

        return $entity; 
    }

    /**
     * Handles displaying of mulligan options
     *
     * @param $position
     * @return int 
     */
    public function manageMulligan($position)
    {
        $table = $this->parsedLogs;

        //separate mulligan block
        $mulliganBlock = array();
        $mulliganEntitiesToChose = array();
        $mulliganId = 0;
        $entityIdArray = array();

        while ($table[$position]['function'] == 'GameState.DebugPrintEntityChoices()') {

            $row = $table[$position];
            array_push($mulliganBlock, $row);
            $position++;
        }

        foreach ($mulliganBlock as $blockRow) {
            if(strpos($blockRow['content'], 'id') === 0) {
                $firstRow = explode(' ', $blockRow['content']);

                if(strpos($firstRow[0], 'id') === 0) {   
                    $parse = $this->parseValue($firstRow[0]);
                    $mulliganId = $parse[1];
                }
            }
            
            if (strpos($blockRow['content'], 'Entities') === 0) {
                $eqlPosiotion = strpos($blockRow['content'], '=');
                $entityBracket = substr($blockRow['content'] , $eqlPosiotion + 1);
                $entityBracket = ltrim($entityBracket, '[');
                $entityBracket = rtrim($entityBracket, ']');

                $entityHeaderValues = $this->decodeEntityHeader($entityBracket);

                if($this->entityContainerById[$entityHeaderValues['id']]) {
                    array_push($mulliganEntitiesToChose, $this->entityContainerById[$entityHeaderValues['id']]);
                    array_push($entityIdArray, $entityHeaderValues['id']);
                }
            }
        }

        $this->eventCollector->addMulliganEvent($row['timestamp'], $mulliganId, $entityIdArray);

        return $position;
    }

    /**
     * Handles players choice from mulligan options
     *
     * @param $position
     * @return void
     */
    public function manageSendChoice($position) 
    {        
        $table = $this->parsedLogs;

        $string = $table[$position]['content'];

        $array = explode(' ', $string);

        $choiceId = $this->findEntityIdInArray($array);

        $position++;

        $row = $table[$position];

        $eqlPosiotion = strpos($row['content'], '=');
        $entityBracket = substr($row['content'] , $eqlPosiotion + 1);
               
        $entityHeaderValues = $this->decodeEntityHeader($entityBracket);

        $id = $entityHeaderValues['id'];
        
        $this->eventCollector->addChoice($row['timestamp'], $choiceId, $id);

        return $position;
    }

    /**
     * Handles basic player moves
     *
     * @param $position
     * @return void
     */
    public function manageSelectOption($position) 
    {        
        $row = $this->parsedLogs[$position];

        $string = $row['content'];

        $array = explode(' ', $string);

        $selectParse = $this->parseValue($array[0]);

        $option = $selectParse[1];

        $targetParse = $this->parseValue($array[2]);

        $target =  $targetParse[1];

        $positionParse = $this->parseValue($array[3]);

        $position = $positionParse[1];

        $this->eventCollector->addSelect($row['timestamp'], $option, $target, $position);

    }

    /**
     * Handles all available player moves in current game state
     *
     * @param $position
     * @return void
     */
    public function manageOptions($position)
    {
        $table = $this->parsedLogs;
        $timestamp = $table[$position]['timestamp'];  

        //separate options block
        $optionsBlock = array();

        $optionArray = array();

        while ($table[$position]['function'] == 'GameState.DebugPrintOptions()') {
            array_push($optionsBlock, $table[$position]['content']);
            $position++;
        }

        foreach ($optionsBlock as $string) {
            if(strpos($string, 'id') === 0) {
                $parse = $this->parseValue($string);
                $blockId = $parse[1];
                $optionBlock = new OptionBlock();
                $optionBlock->setBlockId($blockId);
            }

            if(strpos($string, 'option') === 0) {
                
                //new option object
                $option = new Option;
                $option->setBlockId($blockId);

                $string = trim($string, 'option ');
                $typePos = strpos($string, 'type');
                $optionId = substr($string, 0, $typePos -1);

                $option->setOptionNumber($optionId);

                $mainEntityPos = strpos($string, 'mainEntity=');

                $eqlPos = strpos($string, '=');

                $type = substr($string, $eqlPos +1, $mainEntityPos - $eqlPos -2);

                $entityString = substr($string,$mainEntityPos);
                
                $leftBracket = strpos($string, '[');
                $rightBracket = strpos($string, ']');
                
                $entityString = substr($string, $leftBracket, $rightBracket -$leftBracket +1);

                $parse = $this->decodeEntityHeader($entityString);

                if (isset($parse['id'])) {
                    $mainEntityId = $parse['id'];
                    
                    if (isset($this->entityContainerById[$mainEntityId])) {
                        $option->setMainEntity($this->entityContainerById[$mainEntityId]);
                        $optionArray[$optionId]['mainEntity'] = $this->entityContainerById[$mainEntityId];
                    }

                    if(isset($parse['playerId'])){
                        $option->setPlayerId($parse['playerId']);
                    }

                    if(isset($parse['zone'])){
                        $option->setZone($parse['zone']);
                    }

                    if(isset($parse['zonePos'])){
                        $option->setZonePosition($parse['zonePos']);
                    }
                }
                $optionBlock->addOption($option);
            }

            if (strpos($string, 'target') === 0) {
                $string = trim($string, 'target ');
                $entityPos = strpos($string, 'entity');
                $targetId = substr($string, 0, $typePos -1);

                //new target
                $target = new Option();
                $target-> setOptionNumber($targetId);

                $targetEntityPos = strpos($string, 'entity=');
                
                $eqlPos = strpos($string, '=');

                $targetEntity = substr($string, $eqlPos +1, $mainEntityPos - $eqlPos -2);

                $leftBracket = strpos($string, '[');
                $rightBracket = strpos($string, ']');
                
                $entityString = substr($string, $leftBracket, $rightBracket -$leftBracket +1);

                $parse = $this->decodeEntityHeader($entityString);
                
                if (isset($parse['id'])) {
                    $entity = $this->entityContainerById[$parse['id']];

                    $target->setMainEntity($entity);

                    if(isset($parse['playerId'])){
                        $target->setPlayerId($parse['playerId']);
                    }

                    if(isset($parse['zone'])){
                        $target->setZone($parse['zone']);
                    }

                    if(isset($parse['zonePos'])){
                         $target->setZonePosition($parse['zonePos']);
                    }

                    $optionArray[$optionId][$targetId] = $entity;
                    $optionBlock->getOption($optionId)->addTarget($target);
                }
            }
        }

        $this->eventCollector->addOptionBlock($timestamp, $optionBlock);

        return $position-1;
    }

    /**
     * This function handles adding golden card to players inventory
     *
     * @param $position
     * @return void
     */
    private function manageOwnerTriple($position)
    {
        $table = $this->parsedLogs;
        $timestamp = $table[$position]['timestamp'];  
        
        while(!(strpos($table[$position]['content'], 'SUB_SPELL_END') === 0)){
            if(strpos($table[$position]['content'], 'FULL_ENTITY') === 0){

                $var = $this->decodeEntityHeader($table[$position]['content']);
                
                if(isset($var['id'])) {
                    $entityId =$var['id'];
                }

                $this->manageEntity($position);
            }

            $position++;          
        }
        $this->eventCollector->addTriple($timestamp, $entityId);

        return $position;
    }

    /**
     * Removes tripled cards entity from game
     *
     * @param $position
     * @return void
     */
    private function manageTripleMerge($position)
    {
        $table = $this->parsedLogs;
        $timestamp = $table[$position]['timestamp']; 

        $position = $position + 2;

        while (strpos($table[$position]['content'], 'Targets[') === 0) {
            
            $string = $table[$position]['content'];

            $eql = strpos($string, '=') + 2;
            $string = \substr($string , $eql);

            $vars = $this->decodeEntityHeader($string);

            if (isset($vars['zone']) && ($vars['zone'] == 'HAND' || $vars['zone'] == 'PLAY')) {
                $this->eventCollector->addTripleMerge($timestamp, $vars['id']);  
            }

            $position++;
        }

        return $position+1;
    }

    /**
     * Handles combat boards
     *
     * @param $position
     * @return void
     */
    private function manageBlock($position)
    {
        $table = $this->parsedLogs;
        $timestamp = $table[$position]['timestamp'];  
        $content = trim($table[$position]['content'], 'BLOCK_START');
                
        $array = explode(" ",  $content);

        $entityParse = $this->parseValue($array[2]);

        $entity = $entityParse[1];
        $gameEntityId = $this->findEntityIdByName('GameEntity');
        
        $gameVarArray = [
            'GameEntity',
            "",
            $this->entityContainerById[$gameEntityId]->getTagValueFromPairArray('OWNER'),
        ];

        //main Board adding
        if(
            !is_numeric($entity)
            && $table[$position]['function'] == 'GameState.DebugPrintPower()'
            && strpos($table[$position+1]['content'], 'NUM_TURNS_IN_PLAY') > 0 
            &&  !in_array($entity,$gameVarArray)
            && !(strpos($entity , '[') === 0 )
            ){
                
                if ($this->cbtNameCheck == true && $this->cbtAllCheck == false) {
                    $this->currentCombatNr++;

                    $this->cbtNameCheck = false;

                    $this->cbtAllCheck = false;
                }

                $position++;

                $this->testArray[$this->currentCombatNr]["BOARDS"] = array();

                while( !(strpos($table[$position]['content'] ,'BLOCK_END') === 0) ) {

                    $eql = strpos($table[$position]['content'], '=') + 1;
                     
                    $string = $table[$position]['content'];

                    if(strpos($string, ']')){

                        $bracketStart = strpos($string, '[');
                        $bracketEnd = strpos($string, ']');
                        $length = $bracketEnd - $bracketStart;

                        $string = \substr($string , $eql ,  $length);

                        $vars = $this->decodeEntityHeader($string);

                        if(isset($vars['id']) && isset($vars['playerId']) && isset($vars['zonePos'])) {
                            if(isset($this->entityContainerById[$vars['id']])){  
                                $varsEvent = "cbtId=". $this->currentCombatNr .'&board=1&player='.$vars['playerId'].'&entity='. $vars['id'] . '&zonePos=' . $vars['zonePos'];
                            }
                            $this->eventCollector->addCombat($timestamp, $varsEvent);
                            array_push($this->opponentBoardArray, $string);

                            //test
                            array_push($this->testArray[$this->currentCombatNr]["BOARDS"], $vars['id']);
                            
                        }
                    }
                    
                    $position++;
                }
        }

        if(
            !is_numeric($entity)
            && $table[$position]['function'] == 'PowerTaskList.DebugPrintPower()'
            && strpos($table[$position+1]['content'], 'NUM_TURNS_IN_PLAY') > 0 
            &&  !in_array($entity,$gameVarArray)
            && !(strpos($entity , '[') === 0 )
            ){
                $oppPlayerId = FALSE;

                if (isset( $this->entityContainerByName[$entity])) {
                    
                    $oppEntityId = $this->findEntityIdByName($entity);

                    if (isset( $this->entityContainerById[$oppEntityId]) && $this->findEntityIdByName($this->entityContainerById[$oppEntityId]->getTagValueFromPairArray('NAME')) !=null){
                    $heroEntityName = $this->entityContainerById[$oppEntityId]->getTagValueFromPairArray('NAME');

                    $heroEntId =  $this->findEntityIdByName($heroEntityName);

                    $oppPlayerId = $this->entityContainerById[$heroEntId]->getTagValueFromPairArray('PLAYER_ID');
            
                }

                $this->cbtNameCheck = true;
                    
                } elseif ( ($entity == "Bartender" || $entity == "Bob's") && $this->currentCombatNr == 0) {
                    $this->currentCombatNr++;
                } elseif ($entity == "Bartender" || $entity == "Bob's") {
                    $this->cbtNameCheck = true;
                }

                $nameEvent = "cbtId=". $this->currentCombatNr .'&name=' . $entity .'&oppId='.  $oppPlayerId;

                array_push($this->opponentBoardArray, $nameEvent);
                $this->eventCollector->addCombat($timestamp, $nameEvent);

                $this->chceckCbtFinish();
        }



    //alternative board
    if (
        strpos($table[$position]['content'], 'EffectIndex=13') > 0 
        && $table[$position]['function'] == 'GameState.DebugPrintPower()'
        ) {
            $blocks = array();
            $step = $position +1;
            $this->testArray[$this->currentCombatNr]['m'] = array();
            
            $position++;

            if(strpos($table[$position]['content'], ' BLOCK_END') !== 0 ) {

                while( (strpos($table[$position]['content'] ,'TAG_CHANGE') === 0) || (strpos($table[$position]['content'] ,'HIDE_ENTITY') === 0)) {
                    if(strpos($table[$position]['content'], 'TAG_CHANGE') == 0) {
                        $bracketStart = strpos($table[$position]['content'], '[');
                        $bracketEnd = strpos($table[$position]['content'], ']');
                        $length = $bracketEnd - $bracketStart;

                        $bracket = substr($table[$position]['content'], $bracketStart ,  $length);
                        $vars1 = $this->decodeEntityHeader($bracket);

                        if(isset($vars1['zone'])
                        && $vars1['zone'] == 'SETASIDE'){

                            if((strpos($table[$position]['content'] , 'tag=ZONE_POSSITION') > 0)) {
                                dd($table[$position]['content']);
                            }
                        } else {
                            if(
                                isset($vars1['id']) 
                                && !isset($this->tempArray[$this->currentCombatNr][$vars1['id']])
                                && $this->entityContainerById[$vars1['id']]->getTagValueFromPairArray('CARDTYPE') == 'MINION'
                                ) {
                                    
                                    $this->tempArray[$this->currentCombatNr][$vars1['id']] = array();
    
                                    $this->cbtAllCheck = true;
                                    $string = "cbtId=". ($this->currentCombatNr) .'&all=1&player='. $vars1['playerId']. '&entity='. $vars1['id']. '&zonePos=' . $vars1['zonePos'];
                                    $this->eventCollector->addCombat($table[$position]['timestamp'], $string);
                                    array_push($this->testArray[$this->currentCombatNr]['m'] , $vars1['id']);
                                    array_push($this->tempArray[$this->currentCombatNr][$vars1['id']] , $string);
                                }                                    
                        }
                    }
                    $position++;
                }

                $this->chceckCbtFinish();
            }                   
        }    
    }

    /**
     * Returns all events from EventCollector
     *
     * @return array()
     */
    public function getEventCollector()
    {
        return $this->eventCollector->getEventContainer();
    }

    /**
     * Handles reasult of combat
     *
     * @param $position
     * @return int
     */
    public function manageCombatReasult($position)
    {   
        $table = $this->parsedLogs;
        $timestamp = $table[$position]['timestamp'];  
        $combat = new Combat();
        $combat->setId($this->currentCombatNr);

        while(!(strpos($table[$position]['content'], 'SUB_SPELL_END') === 0)){
            if(strpos($table[$position]['content'], 'Source') === 0){
                $lBrct = strpos($table[$position]['content'], '[') ;
                $rBrct = strpos($table[$position]['content'], ']')  ;
                $string = substr($table[$position]['content'] ,$lBrct,$rBrct- $lBrct);
               
                $var = $this->decodeEntityHeader($string);
                
                if(isset($var['id'])) {
                    $entityId =$var['id'];
                    $combat->setWinner($entityId);
                }

                $this->manageEntity($position);
                $var = "cbtId=". $this->currentCombatNr .'&winner=' . $entityId;
                $this->eventCollector->addCombat($timestamp, $var);

                //TEST
                $this->testArray[$this->currentCombatNr]["winner"] = $entityId;
            }

            if(strpos($table[$position]['content'], 'Target') === 0){

                $target = new Option();

                $lBrct = strpos($table[$position]['content'], '[') + 1;
                $rBrct = strpos($table[$position]['content'], ']')  ;

                //tar number
                $targetNr = substr($table[$position]['content'] ,$lBrct ,$rBrct- $lBrct);

                $entityBracket = substr($table[$position]['content'], $rBrct+4);

                $var1 = $this->decodeEntityHeader($entityBracket);

                $target->setOptionNumber($targetNr);

                if (isset($var1['id'])) {

                    $target->setMainEntity($var1['id']);
                    $target->setZonePosition($var1['zonePos']);
                    $target->setPlayerId($var1['playerId']);

                } else {

                    $eqlPos = strpos($table[$position]['content'], '=');

                    $id = trim($eqlPos);
                    $target->setMainEntity($id);
                }

                $combat->addTarget($target);
            }

            if(strpos($table[$position]['content'], 'TAG_CHANGE') === 0)
            {
                $valPos = strpos($table[$position]['content'], 'value');

                $valStr = substr($table[$position]['content'],  $valPos);

                $valStr = trim($valStr," ");

                $eqlPos = strpos($valStr, '=');

                $value = substr($valStr,  $eqlPos+1);

                $combat->addDamage($value);
            }
            $position++;
        }

        foreach ($combat->getTargets() as $key => $target) {

            $var = "cbtId=". $this->currentCombatNr .
                '&damage=' . $combat->getDamageById($key) . 
                '&entity=' . $target->getMainEntity().
                '&zonePos='. $target->getZonePosition().
                '&player='. $target->getPlayerId();
            
            $this->eventCollector->addCombat($timestamp, $var);
        }

        $this->currentCombat =$combat;

        return $position;
    }

    /**
     * Handles damage dealt in combat
     *
     * @param $position
     * @return void
     */
    private function manageCombatDamage($position)
    {
        $table = $this->parsedLogs;

        $dataPos = strpos($table[$position]['content'], "Data");
        $infoPos = strpos($table[$position]['content'], "InfoCount");
        $dataString = substr($table[$position]['content'], $dataPos,$infoPos -  $dataPos -1);

        $eqlPosition1 = strpos($dataString, "=");
        $damage = subStr($dataString,$eqlPosition1 +1 );
        
        $timestamp = $table[$position]['timestamp'];
        
        $position++;
        $eqlPosition = strpos($table[$position]['content'], "=");

        $string = subStr($table[$position]['content'],$eqlPosition +2 );

        $vars = $this->decodeEntityHeader($string);

        if(isset($vars['id']))
        {
            $entity = $this->entityContainerById[$vars['id']];
            if($entity->getTagValueFromPairArray('CARDTYPE') == 'HERO')
            {
                $var = "entity=". $vars['id'] .'&damage=' . $damage;
                $this->eventCollector->addDamage($timestamp, $var);
            }
        }

        return $position;
    }

    /**
     * Checks if all combat data has been collected
     *
     * @return void
     */
    private function chceckCbtFinish()
    {
        if($this->cbtNameCheck == true && $this->cbtAllCheck == true ) {   
            $this->currentCombatNr++;
            $this->cbtNameCheck = false;
            $this->cbtAllCheck = false;
        } 
    }
}