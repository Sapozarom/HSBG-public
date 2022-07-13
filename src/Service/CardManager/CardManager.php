<?php
namespace App\Service\CardManager;

use App\Entity\Card as CardEntity;
use App\Entity\BaseMinion;
use App\Service\EventInterpreter\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This service takes card from EventInterpreter and create Card entity that will be saved in DB
 */
class CardManager
{
    private $manager;

    private $baseCardRepo;


    public function __construct(EntityManagerInterface $em)
    {
        $this->manager = $em;

        $this->baseCardRepo = $this->manager->getRepository("App:BaseMinion");
    }

    /**
     * Create Card entity using data from EventInterpreter 
     *
     * @param $card | card entity from EventIterpreter
     * @return Card | DB entity
     */
    public function copyCard($card)
    {
        $cardEntity = new CardEntity;

        $baseCard = $this->baseCardRepo->findOneBy(['cardId' => $card->getCardId()]) ;
        
        if($baseCard !=null){
            $cardEntity->setName(null);
            $cardEntity->setType(null);
            $cardEntity->setCardId(null);
            $cardEntity->setDbfId(null);
            $cardEntity->setTribe(null);
            $cardEntity->setBaseCard($baseCard);

        } else {
            $cardEntity->setName($card->getName());
            $cardEntity->setType($card->getType());
            $cardEntity->setCardId($card->getCardId());
            $cardEntity->setDbfId($card->getDbId());
            $cardEntity->setTribe($card->getTribe());
        }

        $cardEntity->setHealth($card->getHealth());
        $cardEntity->setAttack($card->getAttack());
        $cardEntity->setPosition($card->getPosition());
        $cardEntity->setTrippleCheck($card->getTripleCheck());
        $cardEntity->setDeathrattle($card->getDeathrattle());
        $cardEntity->setDivShield($card->getDivineShield());
        $cardEntity->setTechLvl($card->getTechLvl());
        $cardEntity->setGolden($card->getGolden());
        $cardEntity->setReborn($card->getReborn());
        $cardEntity->setTechLvl($card->getTechLvl());
        $cardEntity->setShowTrigger($card->getTrigger());
        $cardEntity->setPoisonous($card->getPoisonous());
        $cardEntity->setLegendary($card->getLegendary());
        $cardEntity->setTaunt($card->getTaunt());
        $cardEntity->setTechLvl($card->getTechLvl());
        $cardEntity->setFrozen($card->getFrozen());
        $cardEntity->setdivShield($card->getDivineShield());

        return $cardEntity;
    }
}