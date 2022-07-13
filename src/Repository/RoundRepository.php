<?php

namespace App\Repository;

use App\Entity\Round;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Round|null find($id, $lockMode = null, $lockVersion = null)
 * @method Round|null findOneBy(array $criteria, array $orderBy = null)
 * @method Round[]    findAll()
 * @method Round[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Round::class);
    }
    
    /**
     * Gets round, of specific game, by its number
     *
     * @param Int $gameId
     * @param Int $roundNumber
     * @return Round|null
     */
    public function findRoundByNumber($gameId , $roundNumber): ?Round
    {
        return $this->createQueryBuilder('r')
            ->where('r.game = :gameId')
            ->andWhere('r.roundNumber = :roundNumber')
            ->setParameter('gameId', $gameId)
            ->setParameter('roundNumber', $roundNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
}
