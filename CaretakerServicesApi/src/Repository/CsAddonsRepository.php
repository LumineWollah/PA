<?php

namespace App\Repository;

use App\Entity\CsAddons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsAddons>
 *
 * @method CsAddons|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsAddons|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsAddons[]    findAll()
 * @method CsAddons[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsAddonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsAddons::class);
    }

//    /**
//     * @return CsAddons[] Returns an array of CsAddons objects
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

//    public function findOneBySomeField($value): ?CsAddons
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
