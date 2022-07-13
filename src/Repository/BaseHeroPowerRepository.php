<?php

namespace App\Repository;

use App\Entity\BaseHeroPower;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @method BaseHeroPower|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseHeroPower|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseHeroPower[]    findAll()
 * @method BaseHeroPower[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BaseHeroPowerRepository extends ServiceEntityRepository
{
    private $dataPath;

    private $filesystem;

    public function __construct(ManagerRegistry $registry, ContainerBagInterface $params, Filesystem $fs)
    {
        parent::__construct($registry, BaseHeroPower::class);

        $this->dataPath = $params->get('app.data_path');

        $this->filesystem = $fs;
    }

    /**
     * Selects all data from DB (BaseHeroPower table) and saves them as JSON file 
     * in folder named after build ID number
     *
     * @param integer $build
     * @return bool
     */
    public function parseAllToJson($build = 88998)
    {
        $allRecords = $this->findAll();

        $allHeroPowers = array();

        foreach ($allRecords as $value) {
            $newHeroPower = array();
            
            $newHeroPower['cardId'] = $value->getCardID();
            $newHeroPower['name'] = $value->getName();
            $newHeroPower['dbfId'] = $value->getDbfId();

            array_push($allHeroPowers, $newHeroPower);
        }
        
        $heroPowerData = array();
        $heroPowerData['build'] = $build;
        $heroPowerData['data'] = $allHeroPowers;
        $jsonArray = json_encode($heroPowerData);

        $dataPath = $this->dataPath;
        
        $path = $dataPath .'/builds/'.$build.'/'.$build. '_heroPowerData.json';
  
        $this->filesystem->dumpFile($path, $jsonArray);

        return true;
    }
}
