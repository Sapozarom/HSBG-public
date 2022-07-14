<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }
    
    /**
     * Gets 5 last uploaded public games 
     *
     * @return Game[]
     */
    public function findNewPublicGames()
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.public = true')
            ->orderBy('g.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all games uploaded by specific user
     *
     * @param $user
     * @return void
     */
    public function findUserGames($user)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.user = :val')
            ->setParameter('val', $user)
            ->orderBy('g.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Return array of compositions played by specific player and order them by amount of games played
     *
     * @param $user 
     * @param $limit | limit of reasults
     * @return void
     */
    public function findMostPlayedComp($user, $limit)
    {   
        $qb = $this->createQueryBuilder('g');
        return $qb
            ->andWhere('g.user = :val')
            ->setParameter('val', $user)
            ->select('count(g) as games, g.composition')
            ->groupBy('g.composition')
            ->orderBy('count(g)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Return array of compositions played by specific player and ordered by best average win rate
     *
     * @param $user 
     * @param $limit | limit of reasults
     * @return void
     */
    public function findBestComp($user)
    {   
        $qb1 = $this->createQueryBuilder('g');
        $comps = $qb1
            ->andWhere('g.user = :val')
            ->setParameter('val', $user)
            ->select('count(g) as games, g.composition')
            ->groupBy('g.composition')
            ->orderBy('count(g)', 'DESC')
            ->getQuery()
            ->getResult();
        
        $qb2 = $this->createQueryBuilder('g');

        $bestComps = array();
        $bestCompoposition = array();
        
        foreach ($comps as $comp){
            $placementSum = $qb2
            ->andWhere('g.user = :val')
            ->setParameter('val', $user)
            ->andWhere('g.composition = :comp')
            ->setParameter('comp', $comp['composition'])
            ->select('sum(g.placement) as sum')
            ->getQuery()
            ->getSingleScalarResult();
        
            if ($comp['games'] > 0) {
                $comp['avg'] = $placementSum / $comp['games'];
            }

            if (isset($bestCompoposition[0])) {

                $avg = $bestCompoposition[0]['avg'];

                if ($avg > $comp['avg']) {
                    $bestCompoposition[0] = $comp;
                }
                
            } else {
                $bestCompoposition[0] = $comp;
            }

            array_push($bestComps, $comp);
        }
            return $bestCompoposition[0];
    }

    /**
     * Handles sorting and prepares query used later by pagination bundle
     *
     * @param  $user
     * @param  $sortType
     * @param  $composition
     * @param  $hero
     * @return $gamesQuery | QuerryBuilde querry
     */
    public function userGamesQuery($user, $sortType, $composition, $hero)
    {
        $qb = $this->createQueryBuilder('g')        
        ->andWhere('g.user = :val')
        ->setParameter('val', $user)
        ->Join('g.owner', 'o');

        //dd($composition);
        if ($composition != 'All') {
            $qb->andWhere('g.composition = :cmp')
                ->setParameter('cmp', $composition);
        }

        if ($hero != 'All') {
            $qb->Join('o.hero', 'h')
                ->andWhere('h.id = :hero')
                ->setParameter('hero', $hero);
        }

        switch ($sortType) {
            case 'oldest':
                $qb->orderBy('g.id', 'ASC');
                break;
            case 'best-finish':
                $qb->orderBy('g.placement', 'ASC');
                break;
            case 'worst-finish':
                $qb->orderBy('g.placement', 'Desc');
                break;
            case 'composition':
                $qb->orderBy('g.composition', 'Asc');
                break;
            case 'hero':
                $qb
                ->orderBy('o.hero', 'Asc');
                break;    
            default:
                $qb->orderBy('g.id', 'DESC');
                break;
        }

        $gamesQuery = $qb->getQuery();

        return $gamesQuery;
    }

    /**
     * Return array of heroes from all games uploaded by user
     *
     * @param $user
     * @return array()
     */
    public function finduploadedHeroes($user)
    {   
        $qb1 = $this->createQueryBuilder('g');
        $heroes = $qb1
            ->andWhere('g.user = :val')
            ->setParameter('val', $user)
            ->Join('g.owner', 'o')
            ->Join('o.hero', 'h')
            ->select('count(g) as games, h.name, h.id')
            ->groupBy('h.name')
            ->orderBy('h.name', 'ASC')
            ->getQuery()
            ->getResult();
            
        return $heroes;
    }

    /**
     * Finds all champions played by user and order them by amount of games played DESC
     *
     * @param  $user
     * @return array()
     */
    public function findFavoriteChampions($user)
    {   
        $qb1 = $this->createQueryBuilder('g');

        if ($user != null) {
            $qb1
            ->andWhere('g.user = :val')
            ->setParameter('val', $user);
        }

        $heroes = $qb1
            ->Join('g.owner', 'o')
            ->Join('o.hero', 'h')
            ->select('count(g) as games, h.name, h.id')
            ->groupBy('h.name')
            ->orderBy('count(g)', 'desc')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
            
        return $heroes;
    }

    /**
     * Finds all champions played by user and order them by average win rate DESC
     *
     * @param  $user
     * @return array()
     */
    public function findBestChampions($user)
    {   
        $qb1 = $this->createQueryBuilder('g');

        if ($user != null) {
            $qb1
            ->andWhere('g.user = :val')
            ->setParameter('val', $user);
        }

        $heroes = $qb1
            ->Join('g.owner', 'o')
            ->Join('o.hero', 'h')
            ->select('count(g) as games, sum(g.placement) as placementSum, h.name, h.id, ( sum(g.placement) / count(g)  ) as avg')
            ->groupBy('h.name')
            ->orderBy('avg', 'asc')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
            
        return $heroes;
    }

    /**
     * Finds 3 most played compositions of specific user
     *
     * @param $user
     * @return array()
     */
    public function findFavoriteCompositions($user)
    {   
        $qb1 = $this->createQueryBuilder('g');

        if ($user != null) {
            $qb1
            ->andWhere('g.user = :val')
            ->setParameter('val', $user);
        }

        $comps = $qb1
            ->select('count(g) as games, g.composition')
            ->groupBy('g.composition')
            ->orderBy('count(g)', 'desc')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
            
        return $comps;
    }

    /**
     * Finds 3 best composition of specific player (by avg win rate)
     *
     * @param $user
     * @return array()
     */
    public function findBestCompositions($user)
    {   
        $qb1 = $this->createQueryBuilder('g');
        
        if ($user != null) {
            $qb1
            ->andWhere('g.user = :val')
            ->setParameter('val', $user);
        }

        $comps = $qb1
            ->select('count(g) as games, g.composition, ( sum(g.placement) / count(g)  ) as avg')
            ->groupBy('g.composition')
            ->orderBy('avg', 'asc')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
            
        return $comps;
    }
   
    /**
     * Returns name array of all champions played by specific user
     *
     * @param $user
     * @return void
     */
    public function getAllPlayedHeroeNames($user)
    {
        $heroes = $this->finduploadedHeroes($user);

        $nameArray = array();

        foreach ($heroes as $hero) {
            array_push($nameArray,$hero['name']);
        }

        return $nameArray;
    }

    /**
     * Return sum of games and average win rate of selected games 
     *
     * @param $querry | from querry buider, same querry that is used for pagination
     * @return array()
     */
    public function getStats($querry) {

        $games = $querry->getResult();

        $placementSum = 0;
        $stats = array();

        foreach ($games as $game) {
            $placementSum = $placementSum + $game->getPlacement();
        }
        if (count($games) > 0) {
            $stats['games'] = count($games);
            $stats['avg'] = $placementSum/count($games);
        } else {
            $stats['games'] = 0;
            $stats['avg'] = 0;
        }

        return $stats;
    }

    /**
     * Creates stat array used in Admin Panel. Counts all games, public games and private games.
     *
     * @return Int[]
     */

    public function createStatsForAdmin(){

        $allStats = array();

        $qb1 = $this->createQueryBuilder('g');
        $allGames = $qb1
        ->select('count(g) as games')
        ->getQuery()
        ->getSingleResult();
        
        $allStats['games'] = $allGames['games'];

        $qb2 = $this->createQueryBuilder('g');
        $allPublic = $qb2
        ->select('count(g) as public')
        ->andWhere('g.public = true')
        ->getQuery()
        ->getSingleResult();

        $allStats['public'] = $allPublic['public'];

        $allStats['private'] = $allGames['games'] - $allPublic['public'];

        return $allStats;
    }
}
