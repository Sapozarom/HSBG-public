<?php

namespace App\Repository;

use App\Entity\BaseHero;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @method BaseHero|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseHero|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseHero[]    findAll()
 * @method BaseHero[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BaseHeroRepository extends ServiceEntityRepository
{
    private $dataPath;

    private $filesystem;

    public function __construct(ManagerRegistry $registry, ContainerBagInterface $params, Filesystem $fs)
    {
        parent::__construct($registry, BaseHero::class);
        
        $this->dataPath = $params->get('app.data_path');

        $this->filesystem = $fs;
    }

    /**
     * Searches for ID of hero with specific name
     *
     * @param $name
     * @return Int|null
     */
    public function findNameById($name): ?Array
    {
        return $this->createQueryBuilder('b')
            ->select('b.name')
            ->andWhere('b.id = :val')
            ->setParameter('val', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    /**
     * Creates array of all hearo IDs
     *
     * @return Array
     */
    public function getHeroArray(): ?Array
    {
        $result = $this->createQueryBuilder('h')
            ->select('h.id')
            ->getQuery()
            ->getResult();
        
        $heroArray = array();
            
        foreach ($result as $key => $value) {
            array_push($heroArray, $value['id']);
        }  
        array_push($heroArray, 'All');
        return  $heroArray;
    }

    /**
     * Selects all data from DB (BaseHero table) and saves them as JSON file 
     * in folder named after build ID number
     *
     * @param integer $build
     * @return bool
     */
    public function parseAllToJson($build = 88998)
    {
        $allRecords = $this->findAll();

        $allHeroes = array();

        foreach ($allRecords as $value) {
            $newHero = array();
            
            $newHero['cardId'] = $value->getCardID();
            $newHero['name'] = $value->getName();
            $newHero['dbfId'] = $value->getDbfId();

            array_push($allHeroes, $newHero);
        }
        
        $herorData = array();
        $heroData['build'] = $build;
        $heroData['data'] = $allHeroes;
        $jsonArray = json_encode($heroData);

        $dataPath = $this->dataPath;
        
        $path = $dataPath .'/builds/'.$build.'/'.$build. '_heroData.json';
  
        $this->filesystem->dumpFile($path, $jsonArray);

        return true;
    }
}
