<?php

namespace App\Service\HSapi;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Service used for fetching data from https://hearthstoneapi.com/. This API is
 * used for obtaining most of data about Cards, Powers and Heroes
 */
class HeroFinder {

    private $client;

    private $dataPath;

    private $filesystem;

    private $currentBuild;
    
    
    public function __construct(HttpClientInterface $client, ContainerBagInterface $params, Filesystem $fs)
    {
        $this->client = $client;

        $this->dataPath = $params->get('app.data_path');

        $this->currentBuild = $params->get('app.current_build');

        $this->filesystem = $fs;
    }


    /**
     * This method gets all data about cards from Battleground mode
     *
     * @return array() | PHP data
     */
    public function getAllCards()
    {
        $response = $this->client->request(
            'GET',
            'https://omgvamp-hearthstone-v1.p.rapidapi.com/cards/sets/Battlegrounds', [
                'headers' => [
                    'x-rapidapi-host' => 'omgvamp-hearthstone-v1.p.rapidapi.com',
                    'X-RapidAPI-Key' => '90d6789f04msh0718da318893b91p1fb79ajsnf0766206ef7a',
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $content = $response->toArray();

        return $content;
    }

    /**
     * Method used to save data about heroes from API into a JSON datafile that
     * is placed in folder named after current HS client build ID number
     *
     * @param array $idArray | IDs of all active champions, from BlizzApi Service
     * @param integer $build | ID of currend buid, global param
     * @return bool
     */
    public function createHeroDataFile($idArray, $build = null)
    {
        $bgCards = $this->getAllCards();

        if ($build == null) {
            $build = $this->currentBuild;
        }

        $captured = array();

        foreach ($bgCards as $value) {
            if (isset($value['dbfId']) && in_array($value['dbfId'], $idArray) ) {

                $newHero = array();
                
                $newHero['cardId'] = $value['cardId'];
                $newHero['name'] = $value['name'];
                $newHero['dbfId'] = $value['dbfId'];

                array_push($captured, $newHero);
            }
        }
        
        $heroData = array();
        $heroData['build'] = $build;
        $heroData['data'] = $captured;

        $jsonArray = json_encode($heroData);

        $this->createFile('hero',$jsonArray, $build);
        
        return true;
    }

    /**
     * Method used to save data about hero powers from API into a JSON datafile
     * that is placed in folder named after current HS client build ID number
     *
     * @param integer|null $build | ID of currend buid, global param
     * @return bool
     */
    public function createHeroPowerDataFiles($build = null)
    {
        $bgCards = $this->getAllCards();

        if ($build == null) {
            $build = $this->currentBuild;
        }

        $captured = array();

        foreach ($bgCards as $value) {
            if (isset($value['type']) && $value['type'] == 'Hero Power')  {

                $newHeroPower = array();
                
                $newHeroPower['cardId'] = $value['cardId'];
                $newHeroPower['name'] = $value['name'];
                $newHeroPower['dbfId'] = $value['dbfId'];

                array_push($captured, $newHeroPower);
            }
        }

        $heroData = array();
        $heroData['build'] = $build;
        $heroData['data'] = $captured;

        $jsonArray = json_encode($heroData);

        $this->createFile('heroPower',$jsonArray, $build);
        
        return true;
    }

    /**
     * Method used to save data about hero powers from API into a JSON datafile
     * that is placed in folder named after current HS client build ID number
     *
     * @param integer|null $build | ID of currend buid, global param
     * @return bool
     */
    public function createCardDataFile($build = null)
    {
        $bgCards = $this->getAllCards();

        if ($build == null) {
            $build = $this->currentBuild;
        }

        $captured = array();

        foreach ($bgCards as $value) {
            if (isset($value['type']) && $value['type'] != 'Enchantment')  {

                $newCard = array();
                
                $newCard['cardId'] = $value['cardId'];
                $newCard['name'] = $value['name'];
                $newCard['dbfId'] = $value['dbfId'];
                $newCard['type'] = $value['type'];

                if (isset($value['race'])) {
                    $newCard['tribe'] = $value['race'];
                } else {
                    $newCard['tribe'] = 'Neutral';
                }
                
                array_push($captured, $newCard);
            }
        }

        $cardData = array();
        $cardData['build'] = $build;
        $cardData['data'] = $captured;
        //dd($cardData);
        $jsonArray = json_encode($cardData);

        $this->createFile('card',$jsonArray, $build);
        
        return true;
    }

    /**
     * Method used to save all 3 JSON datafile that are placed in folder named
     * after current HS client build ID number
     *
     * @param integer|null $build | ID of currend buid, global param
     * @return bool
     */
    public function createAllDataFiles($heroIdArray = null, $build = null)
    {
        $bgCards = $this->getAllCards();

        if ($build == null) {
            $build = $this->currentBuild;
        }

        $allHeroes = array();
        $allHeroPowers = array();
        $allCards = array();

        foreach ($bgCards as $value) {
            if (isset($value['type']) && $value['type'] != 'Enchantment')  {

                $newCard = array();
                
                $newCard['cardId'] = $value['cardId'];
                $newCard['name'] = $value['name'];
                $newCard['dbfId'] = $value['dbfId'];
                $newCard['type'] = $value['type'];

                if (isset($value['race'])) {
                    $newCard['tribe'] = $value['race'];
                } else {
                    $newCard['tribe'] = 'Neutral';
                }

                array_push($allCards, $newCard);

                if (isset($value['dbfId']) && in_array($value['dbfId'], $heroIdArray) ) {

                    $newHero = array();
                    
                    $newHero['cardId'] = $value['cardId'];
                    $newHero['name'] = $value['name'];
                    $newHero['dbfId'] = $value['dbfId'];
    
                    array_push($allHeroes, $newHero);
                }

                if (isset($value['type']) && $value['type'] == 'Hero Power')  {

                    $newHeroPower = array();
                    
                    $newHeroPower['cardId'] = $value['cardId'];
                    $newHeroPower['name'] = $value['name'];
                    $newHeroPower['dbfId'] = $value['dbfId'];
    
                    array_push($allHeroPowers, $newHeroPower);
                }
            }
        }

        $cardData = array();
        $cardData['build'] = $build;
        $cardData['data'] = $allCards;
        $jsonArray = json_encode($cardData);
        $this->createFile('card',$jsonArray, $build);

        $heroData = array();
        $heroData['build'] = $build;
        $heroData['data'] = $allHeroes;
        $jsonArray = json_encode($heroData);
        $this->createFile('hero',$jsonArray, $build);

        $heroPowerData = array();
        $heroPowerData['build'] = $build;
        $heroPowerData['data'] = $allHeroPowers;
        $jsonArray = json_encode($heroPowerData);
        $this->createFile('heroPower',$jsonArray, $build);
        
        return true;
    }


    /**
     * Method used for managing filesystem. Saves data into specific JSON file.
     *
     * @param String $filename
     * @param Json $content
     * @param Int $build
     * @return void
     */
    private function createFile($filename, $content, $build)
    {
        $dataPath = $this->dataPath;
        
        $path = $dataPath .'/builds/'.$build.'/'.$build. '_'.$filename.'Data.json';
  
        $this->filesystem->dumpFile($path, $content);
    }
}

