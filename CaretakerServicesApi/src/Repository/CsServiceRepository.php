<?php

namespace App\Repository;

use App\Entity\CsService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsService>
 *
 * @method CsService|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsService|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsService[]    findAll()
 * @method CsService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsService::class);
    }

//    /**
//     * @return CsService[] Returns an array of CsService objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CsService
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findAvailableServices(array $idsToExclude)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.id NOT IN (:ids)')
            ->setParameter('ids', $idsToExclude)
            ->getQuery()
            ->getResult();
    }
}
