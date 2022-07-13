<?php

namespace App\Repository;

use App\Entity\Combat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Combat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Combat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Combat[]    findAll()
 * @method Combat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CombatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Combat::class);
    }
}
