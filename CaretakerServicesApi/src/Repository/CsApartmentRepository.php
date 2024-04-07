<?php

namespace App\Repository;

use App\Entity\CsApartment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsApartment>
 *
 * @method CsApartment|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsApartment|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsApartment[]    findAll()
 * @method CsApartment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsApartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsApartment::class);
    }

//    /**
//     * @return CsApartment[] Returns an array of CsApartment objects
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

//    public function findOneBySomeField($value): ?CsApartment
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
