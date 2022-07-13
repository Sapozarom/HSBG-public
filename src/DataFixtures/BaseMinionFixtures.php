<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\BaseMinion;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * Data fixtures for BaseMinion Table. It takes data from JSON file based on
 * global parameter 'app.supported_build'
 */
class BaseMinionFixtures extends Fixture
{
    private $publicPath;

    public function __construct(ContainerBagInterface $params)
    {
        $this->dataPath = $params->get('app.data_path');
        $this->supportedBuild = $params->get('app.supported_build');
    }

    public function load(ObjectManager $manager)
    {
        $filename = $this->supportedBuild.'_cardData.json';
        $path = $this->dataPath.'/builds/'. $this->supportedBuild.'/'.$filename;
        
        $file = new \SplFileObject($path);
        
        $string =  $file->fgets(); 
        
        $cardArray = json_decode($string, true);

        foreach ($cardArray['data'] as $value) {

            $newCard = new BaseMinion;

            $newCard->setCardId($value['cardId']);
            $newCard->setName($value['name']);
            $newCard->setDbfId(intval($value['dbfId']));
            $newCard->setType($value['type']);
            $newCard->setTribe($value['tribe']);

            $manager->persist($newCard);
        }

        $manager->flush();
    }
}   
