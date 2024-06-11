<?php

namespace App\Repository;

use App\Entity\CsCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsCategory>
 *
 * @method CsCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsCategory[]    findAll()
 * @method CsCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsCategory::class);
    }

//    /**
//     * @return CsCategory[] Returns an array of CsCategory objects
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

//    public function findOneBySomeField($value): ?CsCategory
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
