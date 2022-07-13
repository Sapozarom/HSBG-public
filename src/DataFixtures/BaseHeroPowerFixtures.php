<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\BaseHeroPower;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * Data fixtures for BaseHeroPower Table. It takes data from JSON file based on
 * global parameter 'app.supported_build'
 */
class BaseHeroPowerFixtures extends Fixture
{
    private $hsApi;

    private $blizzApi;

    private $entityManager;

    private $dataPath;

    public function __construct(ContainerBagInterface $params)
        {
            $this->dataPath = $params->get('app.data_path');
            $this->supportedBuild = $params->get('app.supported_build');
        }

    public function load(ObjectManager $manager)
    {
        $filename = $this->supportedBuild.'_heroPowerData.json';
        $path = $this->dataPath.'/builds/'. $this->supportedBuild.'/'.$filename;

        //$myfile = readfile($filename, $path);
        
        $file = new \SplFileObject($path);
        
        $string =  $file->fgets(); 
        
        $powerArray = json_decode($string, true);

        foreach ($powerArray['data'] as $value) {

            $newHeroPower = new BaseHeroPower;

            $newHeroPower->setCardId($value['cardId']);
            $newHeroPower->setName($value['name']);
            $newHeroPower->setDbfId(intval($value['dbfId']));

            $manager->persist($newHeroPower);
        }

        $manager->flush();
        

    }
}
