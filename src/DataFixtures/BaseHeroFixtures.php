<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\BaseHero;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * Data fixtures for BaseHero Table. It takes data from JSON file based on
 * global parameter 'app.supported_build'
 */
class BaseHeroFixtures extends Fixture
{
    private $dataPath;

    private $supportedBuild;

    public function __construct(ContainerBagInterface $params)
    {
        $this->dataPath = $params->get('app.data_path');
        $this->supportedBuild = $params->get('app.supported_build');
    }

    public function load(ObjectManager $manager)
    {
        $filename = $this->supportedBuild.'_heroData.json';
        $path = $this->dataPath.'/builds/'. $this->supportedBuild.'/'.$filename;
        
        $file = new \SplFileObject($path);
        
        $string =  $file->fgets(); 
        
        $powerArray = json_decode($string, true);

        foreach ($powerArray['data'] as $value) {

            $newHero = new BaseHero;

            $newHero->setCardId($value['cardId']);
            $newHero->setName($value['name']);
            $newHero->setDbfId(intval($value['dbfId']));

            $manager->persist($newHero);
            
        }

        $manager->flush();
        
    }
}
