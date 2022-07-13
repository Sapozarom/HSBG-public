<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Finds all events ascribed to specific round
     * 
     * @return Event[] Returns an array of Event objects
     */
    
    public function findRoundEvents($roundId)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.round = :val')
            ->setParameter('val', $roundId)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
