<?php

namespace App\Service\SaveGame;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\EventInterpreter\EventInterpreter;
use App\Service\CardManager\CardManager;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

use App\Service\EventInterpreter\Entity\Game;
use App\Service\EventInterpreter\Entity\Player;
use App\Service\EventInterpreter\Entity\HeroPower;
use App\Service\EventInterpreter\Entity\Round;
use App\Service\EventInterpreter\Entity\Combat;
use App\Service\EventInterpreter\Entity\CombatEvent;
use App\Service\EventInterpreter\Entity\Event;

use App\Entity\Game as GameEntity;
use App\Entity\Player as PlayerEntity;
use App\Entity\Round as RoundEntity;
use App\Entity\Combat as CombatEntity;
use App\Entity\Board as BoardEntity;
use App\Entity\Event as EventEntity;
use App\Entity\User;
use App\Entity\BaseHero;
use App\Entity\BaseHeroPower;
use App\Entity\SingleGameFile;

/**
 * Main service that maintain processing of game file.  
 */
class SaveGame
{
    private $entityManager;

    private $publicPath;

    private $eventInterpreter;

    private $cardManager;

    private $lastRoundCombat = null;
    
    /**
     * @param EntityManagerInterface $em 
     * @param CardManager $cm | service that chenage game data into card object
     * @param ContainerBagInterface $params
     */
    public function __construct(EntityManagerInterface $em, CardManager $cm, ContainerBagInterface $params)
    {
        $this->entityManager = $em;
        
        $this->cardManager = $cm;

        $this->publicPath = $params->get('app.public_path');
    }

    /**
     * Processes game file and save data to DB
     * 
     * @param  $filename | Name of processed file
     * @param  $userId | Id of user that uploaded file
     */
    public function saveGameToDb($filename, $userId)
    {
        $entityManager = $this->entityManager;

        //service for single card management
        $cm = $this->cardManager;

        //fetching repositories
        $fileRepo = $entityManager->getRepository("App:SingleGameFile");
        $userRepo = $entityManager->getRepository("App:User");
        $heroRepo = $entityManager->getRepository("App:BaseHero");
        $heroPowerRepo = $entityManager->getRepository("App:BaseHeroPower");

        $file = $fileRepo->findOneBy(['filename' => $filename]);

        $filename = $file->getFilename();

        //Service that runs thorugh file and returning game divided round by round
        $eventInterpreter = new EventInterpreter();
        $eventInterpreter->interpreteGame($filename, $this->publicPath);
        $parsedGame = $eventInterpreter->getGame();

        $user = $userRepo->findOneBy(['id' => $userId]);

        //Saving data from EventInterpreter into DB
        $game = new GameEntity();
        $game->setUser($user);
        $game->setTribes($parsedGame->getTribes());
        $game->setPlacement($parsedGame->getFinalPlacement());
        
        //add players
        foreach ($parsedGame->getPlayers() as $parsedPlayer) {
            
            $player = new PlayerEntity();

            $player->setName($parsedPlayer->getName());
            $player->setGame($game);
            $player->setPlayerId($parsedPlayer->getPlayerId());

            $hero = $heroRepo->findOneBy(['cardId' => $parsedPlayer->getHero()]);
            $player->setHero($hero);

            $heroPower = $heroPowerRepo->findOneBy(['cardId' => $parsedPlayer->getHeroPower()->getCardId()]);
            $player->setHeroPower($heroPower);

            if ($parsedPlayer->getPlayerId() == $parsedGame->getOwner()->getPlayerId()) {
                $game->setOwner($player);
            }
            
            $entityManager->persist($player);
        }

        //add rounds
        foreach ($parsedGame->getRounds() as $parsedRound) {
            
        if($parsedRound->getCombat() !=null) {
            $round = new RoundEntity();
            $round->setGame($game);
            $round->setRoundNumber($parsedRound->getRoundNumber());
            $round->setLeaderboard($parsedRound->getLeaderboard());
            $round->setNextOppId($parsedRound->getNextOpponent()->getPlayerId());

            //add events to round
            foreach ($parsedRound->getEvents() as $parsedEvent) {

                if ($parsedEvent->getContent() == 'GO') {

                    $round->setLastRound(true);

                } elseif (!($parsedEvent instanceof CombatEvent) ) {
                    $event = new EventEntity;

                    $event->setRound($round);
                    $event->setText($parsedEvent->getContent());
                    $event->setType($parsedEvent->getType());
                    $event->setPlayerHealth($parsedEvent->getPlayerHealth());
                    $event->setGold($parsedEvent->getPlayerGold());
                    $event->setTavernTier($parsedEvent->getTavernTier());
                    $event->setUpgradeCost($parsedEvent->getUpgradeCost());
                    $event->setRerollCost($parsedEvent->getRerollCost());
                    $event->setPowerUsed($parsedEvent->getHeroPowerUsed());

                    if ($parsedEvent->getTarget() != null) {
                        $actionTarget = $cm->copyCard($parsedEvent->getTarget());
                        $event->setTarget($actionTarget);
                        $entityManager->persist($actionTarget);
                    }

                    $event->setTimestamp($parsedEvent->getTimestamp());

                    //player board
                    if($parsedEvent->getPlayerBoard() != null) {
                        $playerBoard = new BoardEntity();

                        foreach ($parsedEvent->getPlayerBoard() as $key => $parsedCard) {
                            $card = $cm->copyCard($parsedCard);
                            $card->setParseKey($key); //tests only
                            $playerBoard->addCard($card);
    
                            $entityManager->persist($card);
                        }

                        $event->setPlayerBoard($playerBoard);
                        $entityManager->persist($playerBoard);
                    }

                    //shop board
                    if ($parsedEvent->getSecondBoard() != null) {
                        $shop = new BoardEntity();

                        foreach ($parsedEvent->getSecondBoard() as $key => $parsedCard) {
                            $card = $cm->copyCard($parsedCard);
                            $card->setParseKey($key); //tests only
                            $shop->addCard($card);

                            $entityManager->persist($card);
                        }
                        $event->setInnkeeperBoard($shop);
                        $entityManager->persist($shop);
                    }
                    
                    //player hand
                    if($parsedEvent->getHand() !=null) {
                        $hand = new BoardEntity();

                        foreach ($parsedEvent->getHand() as $key => $parsedCard) {
                            $card = $cm->copyCard($parsedCard);
                            $card->setParseKey($key); //tests only
                            $hand->addCard($card);
    
                            $entityManager->persist($card);
                        }

                        $event->setHand($hand);
                        $entityManager->persist($hand);
                    }
                    
                    $entityManager->persist($event);
                }
            }

            //add combat
            if ($parsedRound->getCombat() != null) {
                $combat = new CombatEntity;
                $parsedCombat = $parsedRound->getCombat();
                $combat->setRound($round);
                $combat->setPlayerHealth($parsedCombat->getOwnerHealth());
                $combat->setOppHealth($parsedCombat->getOppHealth());
                $combat->setOppPlayer($parsedCombat->getOppPlayerId());
                $combat->setWinner($parsedCombat->getWinner());
                $combat->setDamage($parsedCombat->getDamage());

                //CBT player board
                $playerCbtBoard = new BoardEntity();

                foreach ($parsedCombat->getPlayerBoard() as $parsedCard) {                  
                    $card = $cm->copyCard($parsedCard);
                    $playerCbtBoard->addCard($card);

                    $entityManager->persist($card);
                }

                //CBT OPP board
                $oppCbtBoard = new BoardEntity();

                foreach ($parsedCombat->getOppBoard() as $parsedCard) {
                    
                    $card = $cm->copyCard($parsedCard);
                    $oppCbtBoard->addCard($card);

                    $entityManager->persist($card);
                }

                $combat->setPlayerBoard($playerCbtBoard);
                $combat->setOppBoard($oppCbtBoard);

                $entityManager->persist($playerCbtBoard);
                $entityManager->persist($oppCbtBoard);
                $entityManager->persist($combat);           
            }
            
            //playerBoard
            $this->lastRoundCombat = $combat;
            $entityManager->persist($round);
        }
    }   

        $comp = $this->findComposition($this->lastRoundCombat->getPlayerBoard()->getCards());
        $game->setComposition($comp);

        $game->setTribes($parsedGame->getTribes());

        //persist
        $entityManager->persist($game);
        $entityManager->flush();
        
    }
    
    /**
     * Function to recognize if players board represent specific tribe type
     * 
     * @param  $board |  players board 
     * 
     * @return  String $comp | name of board composition
     */
    private function findComposition($board)
    {   
        $mech = 0;
        $elemental = 0;
        $beast = 0;
        $quilboar = 0;
        $demon = 0;
        $pirate = 0;
        $dragon = 0;
        $murloc = 0;
        $all = 0;
        $neutral = 0;

        foreach($board as $minion)
        {
            if ($minion->getBaseCard() != null) {
                $tribe = $minion->getBaseCard()->getTribe();
            } else {
                $tribe = $minion->getTribe();
            }

            switch ($tribe) {
                case 'Mech':
                    $mech++;
                    break;
                case 'Elemental':
                    $elemental++;
                    break;
                case 'Beast':
                    $beast++;
                    break;
                case 'Quilboar':
                    $quilboar++;
                    break;     
                case 'Demon':
                    $demon++;
                    break;
                case 'Pirate':
                    $pirate++;
                    break; 
                case 'Dragon':
                    $dragon++;
                    break;
                case 'Murloc':
                    $murloc++;
                    break;
                case 'All':
                    $all++;
                    break;
                case 'NEUTRAL':
                    $neutral++;
                    break;  
                default:
                    break;
            }

        }

        $tribeArray = array();
        
        //tribe  types
        $tribeArray =[
            'Mech' => $mech,
            'Elemental'=> $elemental,
            'Beast' => $beast,
            'Quilboar'=> $quilboar,
            'Demon'=> $demon,
            'Pirate' => $pirate,
            'Dragon'=> $dragon,
            'Murloc'=> $murloc,
            'All'=> $all,
            'NEUTRAL'=> $neutral,
        ];

        $max = max($tribeArray);
        $sum = array_sum($tribeArray);
        $topTribe = array_search(max($tribeArray),$tribeArray);

        if ($sum > 7) {
            $comp = 'ERROR';
        } elseif (($max + $tribeArray['All'] >= 4
            || $max + $tribeArray['All'] >= $sum - ($max + $tribeArray['All']))
            && $topTribe != 'NEUTRAL'
            && $topTribe != 'All'
        ) {
           $comp = $topTribe .'s';
        } else {
            $comp = 'Menagerie';
        }

        return $comp;
    }
}