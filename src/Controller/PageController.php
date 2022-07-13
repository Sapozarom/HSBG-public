<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\GameRepository;
use App\Repository\BaseHeroRepository;
use App\Entity\Game;
use App\Entity\BaseHero;
use App\Entity\Round;
use App\Entity\Event;
use App\Form\Type\FindGameByIdType;
use App\Service\SaveGame\SaveGame;

class PageController extends AbstractController
{
    /**
     * Renders homepage
     * 
     * @Route("/", name="homepage")
     */
    public function index(GameRepository $gameRepo): Response
    { 

        $lastUploadedPublicGames = $gameRepo->findNewPublicGames();
        $comunityFavoriteChampionList = $gameRepo->findFavoriteChampions(null);
        $comunityBestChampionList = $gameRepo->findBestChampions(null);
        $comunityFavoriteCompositionList = $gameRepo->findFavoriteCompositions(null);
        $comunityBestCompositionList = $gameRepo->findBestCompositions(null);
        
        $user = $this->getUser();

        if ($user) {
            $userFavoriteChampionList = $gameRepo->findFavoriteChampions($user);
            $userBestChampionList = $gameRepo->findBestChampions($user);
            $userFavoriteCompositionList = $gameRepo->findFavoriteCompositions($user);
            $userBestCompositionList = $gameRepo->findBestCompositions($user);
        } else {
            $userFavoriteChampionList = null;
            $userBestChampionList = null;
            $userFavoriteCompositionList = null;
            $userBestCompositionList = null;
        }

        return $this->render('homepage/index.html.twig', [
            'lastUploadedPublic' => $lastUploadedPublicGames,
            'communityFavChamp' => $comunityFavoriteChampionList,
            'communityBestChamp' => $comunityBestChampionList,
            'communityFavComp' => $comunityFavoriteCompositionList,
            'communityBestComp' => $comunityBestCompositionList,
            'userFavChamp' => $userFavoriteChampionList,
            'userBestChamp' => $userBestChampionList,
            'userFavComp' => $userFavoriteCompositionList,
            'userBestComp' => $userBestCompositionList,
        ]);
    }

    /**
     * Renders "how it works" page
     * 
     * @Route("/how-it-works", name="how_it_works")
     */
    public function instructions(): Response
    { 
        return $this->render('homepage/howItWorks.html.twig');
    }


    /**
     * Renders game view
     * 
     * @param Int $game | games ID
     * @param Int $round | current round number
     * 
     * @Route("/game/{game}/{round}",
     * defaults={ "round": 1 },
     * name="show_game")
     */
    public function showSingleGame(int $game, int $round): Response
    {     
        $user = $this->getUser();

        $gameObject = $this->getDoctrine()->getRepository(Game::class)
        ->findOneBy(['id' =>$game]);

        if ($gameObject == null) {
            $this->addFlash('fail', "Game with id <b>".$game."</b> doestn't exist");
            return $this->redirectToRoute('homepage', [
            ]);
        } else {
            if ($round > count($gameObject->getRounds())) {
                    $this->addFlash('fail', "Game with id <b>".$game . "</b> doesn't have round nr <b>".$round."</b>");
                return $this->redirectToRoute('homepage', [
                ]);
            }
        }


        if (!($gameObject->getPublic())) {
           
            if ($this->getUser() && $gameObject->getUser()->getId() == $user->getId()) {
                $roundObject = $this->getDoctrine()->getRepository(Round::class)
                ->findRoundByNumber($game, $round);
        
                $players = $gameObject->getPlayers();
        
                $gameObject->countRounds();
                
                return $this->render('game/singleRound.html.twig', [
                    'game' => $gameObject,
                    'round'=> $roundObject,
                    'players' => $players,  
                ]);
            } else {
                $this->addFlash('fail', 'Ups! This game is private');
                
                return $this->redirectToRoute('homepage');
            }
        
        }

        $roundObject = $this->getDoctrine()->getRepository(Round::class)
        ->findRoundByNumber($game, $round);

        $players = $gameObject->getPlayers();

        $gameObject->countRounds();
        
        return $this->render('game/singleRound.html.twig', [
            'game' => $gameObject,
            'round'=> $roundObject,
            'players' => $players,  
        ]);
    }

    /**
     * Changes public status of specific game selected by ID
     * 
     * @param Int $game | games ID
     * 
     * @Route("/public/{game}", name="change_public_status")
     */
    public function changePublicStatus(int $game): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();

        $gameObject = $this->getDoctrine()->getRepository(Game::class)
        ->findOneBy(['id' =>$game]);

        if ($user == $gameObject->getUser()) {

            if ($gameObject->getPublic() != true) {
                $gameObject->setPublic(true);
            } else {
                $gameObject->setPublic(false);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($gameObject);
            $entityManager->flush();
        }

        $players = $gameObject->getPlayers();

        $gameObject->countRounds();
        
        return $this->redirectToRoute('user_games');
    }
    
    /**
    
     */

     /**
      * Handles form from nav bar. If fed with proper game id it redirects to the specific 
      * game view
      * 
      * @param Request $request
      * @return Response
      *
      * @Route("/find-game", name="find_game", methods = {"GET","POST"})
      */
    public function findGame(Request $request)
    {
        if (isset($_POST['id'])) {

            return $this->redirectToRoute('show_game', [
                'game' => $_POST['id'],
            ]);

        } else {
            $this->addFlash('fail', 'Something went wrong, pleaset try again.');
        }
        
        return $this->redirectToRoute('homepage');
    }
        
    /**
     * Renders list of games uploaded by currently logged player
     * 
     * @Route("/user-games", 
     * name="user_games", 
     * )
     */
    public function showAllGames(PaginatorInterface $paginator, Request $request, GameRepository $gameRepo, BaseHeroRepository $heroRepo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        
        $params = $request->query->all();

        //SORT

        //hero
        $heroArray = $heroRepo->getHeroArray();
        if (!isset($params['hero'])) {
            $request->query->add(['hero' => 'All']);
        } else {
            if (!in_array($params['hero'], $heroArray)) {
                $request->query->add(['hero' => 'All']);
                $this->addFlash('fail', "Sorry but this hero doesn't exist");
            }
        }
        $hero = $request->query->get('hero');

        //sort Type
        $sortTypeArray = ['oldest', 'best-finish', 'worst-finish', 'composition', 'hero', 'latest'];
        if (!isset($params['sortBy'])) {
           $request->query->add(['sortBy' => 'latest']);
        } else {
            if (!in_array($params['sortBy'], $sortTypeArray)) {
                $request->query->add(['sortBy' => 'All']);
                $this->addFlash('fail', "Sorry but we don't support this type of sorting");
            }
        }
        $sort = $request->query->get('sortBy');


        //compositions
        
        $compArray = ['All','Beasts','Demons','Dragons','Elementals','Mechs','Menagerie','Murlocs','Pirates','Quilboar'];
        if (!isset($params['comp'])) {
            $request->query->add(['comp' => 'All']);
        } else {
            if (!in_array($params['comp'], $compArray)) {
                $request->query->add(['comp' => 'All']);
                $this->addFlash('fail', "Sorry but composition like this doesn't exist");
            }
        }
        $sortComp = $request->query->get('comp');

        if (!isset($params['limit'])) {
            $request->query->add(['limit' => 10]);
        } else {
            if (!in_array($params['limit'], [10,25,50])) {
                $request->query->add(['limit' => 10]);
                $this->addFlash('fail', 'Sorry but this page limit is unavailable');
            }
        }
        $limit = $request->query->get('limit');

        if ($hero != 'All') {
            $sortHero = $allPlayedHeroes = $this->getDoctrine()->getRepository(BaseHero::class)->findNameById($hero);

            if ($sortHero != null) {
                $sortHeroName = $sortHero['name'];
            } else {

                $sortHeroName = 'All';
            }
            
        } else {
            $sortHeroName = 'All';
            $hero = 'All';
        }

        $query = $gameRepo->userGamesQuery($user, $sort, $sortComp, $hero);
        $statQuery =  $gameRepo->userGamesQuery($user, $sort, $sortComp, $hero);
        $gameStats = $gameRepo->getStats($statQuery);

        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            $limit /*limit per page*/
        );
        
        //most played comp
        $favCompArray = $this->getDoctrine()->getRepository(Game::class)->findMostPlayedComp($user,null);
        $allComps = array();   
        foreach ($favCompArray as $comp) {
                array_push($allComps, $comp['composition']);
        }
        sort($allComps);
        
        //all played heroes
        $allPlayedHeroes = $this->getDoctrine()->getRepository(Game::class)->finduploadedHeroes($user);
       
        
        return $this->render('user/games.html.twig', [
            'pagination' => $pagination,
            'uploadedGames' => $gameStats['games'],
            'avgPlacement' => $gameStats['avg'],
            'allComps'=> $allComps,
            'allPlayedHeroes' => $allPlayedHeroes,
            'sortHero' => $sortHeroName,
        ]);
    }   
}
