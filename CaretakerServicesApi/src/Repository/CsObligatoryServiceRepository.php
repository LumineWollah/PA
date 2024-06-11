<?php

namespace App\Repository;

use App\Entity\CsObligatoryService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsObligatoryService>
 *
 * @method CsObligatoryService|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsObligatoryService|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsObligatoryService[]    findAll()
 * @method CsObligatoryService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsObligatoryServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsObligatoryService::class);
    }

//    /**
//     * @return CsObligatoryService[] Returns an array of CsObligatoryService objects
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

//    public function findOneBySomeField($value): ?CsObligatoryService
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
