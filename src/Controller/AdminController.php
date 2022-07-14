<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\BlizzApi\TokenFinder;
use App\Service\HSapi\HeroFinder;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\BaseMinion;
use App\Entity\BaseHero;
use App\Entity\BaseHeroPower;

use App\Repository\BaseMinionRepository;
use App\Repository\BaseHeroPowerRepository;
use App\Repository\BaseHeroRepository;
use App\Repository\UserRepository;
use App\Repository\GameRepository;

use App\Service\ImageApi\ImageApi;

class AdminController extends AbstractController
{
    /**
     * Simple admin panel used to update data and resources adequate to current build
     * It also displays simple app stats
     * 
     * @Route("/admin-panel", name="admin_panel")
     */
    public function adminPanel(UserRepository $userRepo, GameRepository $gameRepo): Response
    {
        $numberOfAllUsers = $userRepo->countAllUsers();
        $gameStats = $gameRepo->createStatsForAdmin();

        $build = $this->getParameter('app.current_build');
        $suppBuild = $this->getParameter('app.supported_build');

        return $this->render('admin/panel.html.twig', [
            'build' => $build,
            'supportedBuild' => $suppBuild,
            'totalUsers' => $numberOfAllUsers,
            'totalGames' => $gameStats['games'],
            'public' => $gameStats['public'],
            'private' => $gameStats['private'],
        ]);
    }

    /**
     * Creates hero JSON  data file that is attached to specific HS game build.
     * JSON file is later used to upload and compare data in database
     * 
     * @param TokenFinder $tf || Service used for connection with Blizzard API
     * @param HeroFinder $hf || Service used for connection with hearthstoneAPI
     * @return Response
     * 
     * 
     * @Route("/create-hero-data-file", name="create_hero_data_file")
     */
    public function updateAvailableHeroes(TokenFinder $tf, HeroFinder $hf)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $heroIds = $tf->getAllAvailableHeroes();

        $heroDataFile = $hf->createHeroDataFile($heroIds);

        if ($heroDataFile) {
            $this->addFlash('success', "Hero data file has been created");
        }
        
        return $this->redirectToRoute('admin_panel');
    }

    /**
     * Creates heroPower JSON  data file that is attached to specific HS game build.
     * JSON file is later used to upload and compare data in database
     * 
     * @param TokenFinder $tf || Service used for connection with Blizzard API
     * @param HeroFinder $hf || Service used for connection with hearthstoneAPI
     * @return Response
     * 
     * @Route("/create-hero-power-data-file", name="create_hero_power_data_file")
     */
    public function updateAvailableHeroPowers(HeroFinder $hf)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $heroPowerDataFile = $hf->createHeroPowerDataFiles();

        if ($heroPowerDataFile) {
            $this->addFlash('success', "Hero Power data file has been created");
        }

        return $this->redirectToRoute('admin_panel');
    }

    /**
     * Creates card JSON  data file that is attached to specific HS game build.
     * JSON file is later used to upload and compare data in database
     * 
     * @param TokenFinder $tf || Service used for connection with Blizzard API
     * @param HeroFinder $hf || Service used for connection with hearthstoneAPI
     * @return Response
     * 
     * @Route("/create-card-data-file", name="create_card_data_file")
     */
    public function updateAvailableCards(HeroFinder $hf)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $cardDataFile = $hf->createCardDataFile();

        if ($cardDataFile) {
            $this->addFlash('success', "Card data file has been created");
        }

        return $this->redirectToRoute('admin_panel');
    }

    /**
     * Creates all three (card, hero, hero power) JSON  data files that are attached to specific HS game build.
     * JSON file is later used to upload and compare data in database
     * 
     * @param TokenFinder $tf || Service used for connection with Blizzard API
     * @param HeroFinder $hf || Service used for connection with hearthstoneAPI
     * @return Response
     * 
     * 
     * @Route("/create-all-data-file", name="create_all_data_file")
     */
    public function updateAllDataFiles(TokenFinder $tf, HeroFinder $hf)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $heroIds = $tf->getAllAvailableHeroes();

        $alldata = $hf->createAllDataFiles($heroIds);

        if ($alldata) {
            $this->addFlash('success', "All data files have been created");
        }

        return $this->redirectToRoute('admin_panel');;
    }

    /**
     * Scraps current data from database and save them as JSON files
     *
     * @param BaseMinionRepository $minionRepo
     * @param BaseHeroPowerRepository $powerRepo
     * @param BaseHeroRepository $heroRepo
     * @return Response
     * 
     * @Route("/db-to-json", name="db_to_json")
     */

    public function dbToJson(BaseMinionRepository $minionRepo, BaseHeroPowerRepository $powerRepo, BaseHeroRepository $heroRepo)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $powers =  $powerRepo->parseAllToJson();
        $heroes =  $heroRepo->parseAllToJson();
        $cards =  $minionRepo->parseAllToJson();
        
        if ($cards && $heroes && $powers) {
            $this->addFlash('success', "All data files have been created");
        } 

        return $this->redirectToRoute('admin_panel');
    }

    /**
     * Download hero power image resources for all records from database
     * This will take few minutes if you have to download all resources for whole
     * database. Please be patient. If script will crash you should <b>restart it</b>.
     * 
     * @param BaseHeroPowerRepository $powerRepo
     * @param ImageApi $imageApi
     * @param EntityManagerInterface $em
     * @return void
     * @return Response
     * 
     * @Route("/power-to-img", name="power_to_img")
     */
    public function heroPowerToImg(BaseHeroPowerRepository $powerRepo, BaseMinionRepository $minionRepo, ImageApi $imageApi, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $powers =  $powerRepo->findAll();

        foreach ($powers as $value) {
            
            if (!($value->getImage())) {
                $saveImages = $imageApi->downloadImages($value);
                $value->setImage($saveImages);

                $checkCard = $minionRepo->findOneBy(['cardId' => $value->getCardId()]);

                if ($checkCard) {
                    $checkCard->setImage($saveImages);
                }
                
                $em->persist($checkCard);
                $em->persist($value);
                $em->flush();
            }
        }
    
        $this->addFlash('success', "All images have been downloaded");

        return $this->redirectToRoute('admin_panel');
    }

    /**
    * Download hero image resources for all records from database
    * This will take few minutes if you have to download all resources for whole
    * database. Please be patient. If script will crash you should <b>restart it</b>.
    *
    * @param BaseHeroPowerRepository $powerRepo
    * @param BaseMinionRepository $minionRepo
    * @param ImageApi $imageApi
    * @param EntityManagerInterface $em
    * @return void
    * @return Response
    * 
    * @Route("/hero-to-img", name="hero_to_img")
    */
    public function heroToImg(BaseHeroRepository $heroRepo, BaseMinionRepository $minionRepo, ImageApi $imageApi, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $heroes =  $heroRepo->findAll();

        foreach ($heroes as $value) {
            
            if (!($value->getImage())) {
                $saveImages = $imageApi->downloadImages($value);
                $value->setImage($saveImages);

                $checkCard = $minionRepo->findOneBy(['cardId' => $value->getCardId()]);

                if ($checkCard) {
                    $checkCard->setImage($saveImages);
                }
                
                $em->persist($checkCard);
                $em->persist($value);
                $em->flush();
            }
        }

        $this->addFlash('success', "All images have been downloaded");

        return $this->redirectToRoute('admin_panel');
    }

    /**
    * Download minion and spell image resources for all records from database.
    * This will take few minutes if you have to download all resourcess for whole
    * database. Please be patient. If script will crash you should <b>restart it</b>.
    *
    * @param BaseMinionRepository $minionRepo
    * @param ImageApi $imageApi
    * @param EntityManagerInterface $em
    * @return void
    * @return Response
    * 
    * @Route("/minion-to-img", name="minion_to_img")w
    */
    public function minionToImg(BaseMinionRepository $minionRepo, ImageApi $imageApi, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $minions =  $minionRepo->findAll();

        foreach ($minions as $value) {
            
            if (!($value->getImage())) {
                $saveImages = $imageApi->downloadImages($value);
                $value->setImage($saveImages);

                $em->persist($value);
                $em->flush();
            }
        }

        $this->addFlash('success', "All images have been downloaded");

        return $this->redirectToRoute('admin_panel');
    }

    /**
     * For testing custom exceptions
     * 
     * @Route("/error-test", name="error_test")
     */
    public function error(): Response
    { 
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('homepage/error404.html.twig');
    }
}
