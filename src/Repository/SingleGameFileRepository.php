<?php

namespace App\Repository;

use App\Entity\SingleGameFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SingleGameFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method SingleGameFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method SingleGameFile[]    findAll()
 * @method SingleGameFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SingleGameFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SingleGameFile::class);
    }

}
