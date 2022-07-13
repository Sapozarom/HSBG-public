<?php

namespace App\Service\ImageApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;

use App\Entity\BaseHero;
use App\Entity\BaseHeroPower;
use App\Entity\Minion;

/**
 * Service used for fetching data from https://hearthstonejson.com/. This API is
 * only used to download graphics scraped from game client, that are later used 
 * for rendering GUI 
 */
class ImageApi {

    private $entityManager;

    private $dataPath;

    private $currentdBuild;

    private $filesystem;

    public function __construct(HttpClientInterface $client, EntityManagerInterface $em, ContainerBagInterface $params, Filesystem $fs)
    {
         $this->entityManager = $em;
         $this->filesystem = $fs;
         $this->dataPath = $params->get('app.data_path');
         $this->currentdBuild = $params->get('app.current_build');
    }

    /**
     * Default methot for processing and saving data
     *
     * @param BaseHero|BaseHeroPower|BaseMinion $object
     * @return bool|null
     */
    public function downloadImages($object)
    {
            $cardId = $object->getCardId();

            $artPath = "https://art.hearthstonejson.com/v1/256x/".$cardId.".jpg";

            $cardPath = "https://art.hearthstonejson.com/v1/render/latest/enUS/256x/".$cardId.".png";


            $saveArtPath = $this->dataPath .'/images/art';
            $saveCardPath = $this->dataPath .'/images/cards';

            if (!($this->filesystem->exists($saveArtPath))) {
                $this->filesystem->mkdir($saveArtPath);
            }

            if (!($this->filesystem->exists($saveCardPath))) {
                $this->filesystem->mkdir($saveCardPath);
            }
            
            $saveArtPath = $this->dataPath .'/images/art/'.$cardId.'.jpg';
            $saveCardPath = $this->dataPath .'/images/cards/'.$cardId.'.jpg';

            $file_headers = @get_headers($artPath);
            if ( !(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') && !file_exists($artPath)) {
                file_put_contents($saveArtPath, file_get_contents($artPath));
            }

            $check1 = $file_headers;

            $file_headers = @get_headers($cardPath);
            if ( !(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') && !file_exists($artPath)) {
                file_put_contents($saveCardPath, file_get_contents($cardPath));
            }
            
            $check2 = $file_headers;

            //checks if both file were donloaded
            if ($check1 && $check2) {
                return true;
            } elseif (!$check1 || !$check2) {
                return false;
            }

            return null;
    }

}