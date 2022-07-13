<?php

namespace App\Repository;

use App\Entity\BaseMinion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @method BaseMinion|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseMinion|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseMinion[]    findAll()
 * @method BaseMinion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BaseMinionRepository extends ServiceEntityRepository
{
    private $dataPath;

    private $filesystem;

    public function __construct(ManagerRegistry $registry, ContainerBagInterface $params, Filesystem $fs)
    {
        parent::__construct($registry, BaseMinion::class);
        
        $this->dataPath = $params->get('app.data_path');

        $this->filesystem = $fs;
    }

    /**
     * Selects all data from DB (BaseMinion table) and saves them as JSON file
     * in folder named after build ID number
     *
     * @param integer $build
     * @return bool
     */
    public function parseAllToJson($build = 88998)
    {
        $allRecords = $this->findAll();

        $allCards = array();

        foreach ($allRecords as $value) {

            $newCard = array();
            $newCard['cardId'] = $value->getCardID();
            $newCard['name'] = $value->getName();
            $newCard['dbfId'] = $value->getDbfId();
            $newCard['type'] = $value->getType();
            $newCard['tribe'] = $value->getTribe();
            
            array_push($allCards, $newCard);
            
        }

        $cardData = array();
        $cardData['build'] = $build;
        $cardData['data'] = $allCards;

        //dd( $cardData);
        $jsonArray = json_encode($cardData);

        $dataPath = $this->dataPath;
        
        $path = $dataPath .'/builds/'.$build.'/'.$build. '_cardData.json';
  
        $this->filesystem->dumpFile($path, $jsonArray);
        
        return true;
    }
    
}
