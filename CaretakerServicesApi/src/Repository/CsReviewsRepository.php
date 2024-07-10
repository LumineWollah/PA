<?php

namespace App\Repository;

use App\Entity\CsReviews;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsReviews>
 *
 * @method CsReviews|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsReviews|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsReviews[]    findAll()
 * @method CsReviews[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsReviewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsReviews::class);
    }

//    /**
//     * @return CsReviews[] Returns an array of CsReviews objects
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

//    public function findOneBySomeField($value): ?CsReviews
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
