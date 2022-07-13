<?php

namespace App\Service\BlizzApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
// use App\Entity\ApiMinion;

/**
 * Service used for fetching data from https://develop.battle.net/. This API is
 * only used to get all playable heroes in current game build
 */
class TokenFinder {
    
    private $apiKey;
    private $apiSecret;
    private $client;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $em, ContainerBagInterface $params)
    {
        $this->client = $client;
        $this->entityManager = $em;
        $this->apiKey = $params->get('app.blizz_api_key');
        $this->apiSecret = $params->get('app.blizz_api_secret');
    }
    /**
     * Creates AccessToken used later to connect with API
     *
     * @return String | Access Token
     */
    public function createAccessToken()
    {
        $curl_handle = curl_init();
        try {
          curl_setopt($curl_handle, CURLOPT_URL, "https://eu.battle.net/oauth/token");
          curl_setopt($curl_handle, CURLOPT_POSTFIELDS, ['grant_type' => 'client_credentials']);
          curl_setopt($curl_handle, CURLOPT_USERPWD, $this->apiKey . ':' . $this->apiSecret);
          curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl_handle, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
          $response = curl_exec($curl_handle);
          $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
            
          if ($status !== 200) {
            throw new Exception('Failed to create client_credentials access token.');
          }
        return json_decode($response)->access_token;
        } finally {
          curl_close($curl_handle);
        }
    }

    /**
     * Finds all available heroes in Battleground mode and returns array of their IDs 
     *
     * @return Int[]
     */
    public function getAllAvailableHeroes()
    {
        $header = 'Bearer '. $this->createAccessToken();
        $response = $this->client->request(
            'GET',
            'https://us.api.blizzard.com/hearthstone/cards?locale=en_US&collectible=0&gameMode=battlegrounds&tier=hero&pageSize=100', [
                'headers' => [
                    'Authorization' => $header ,
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $content = $response->toArray();
        $heroes = $content['cards'];

        $idArray = array();

        foreach ($heroes as $value) {
            if (isset($value['id'])) {
                array_push($idArray, $value['id']);
            }
        }

        return $idArray;
    }
}
    
    
