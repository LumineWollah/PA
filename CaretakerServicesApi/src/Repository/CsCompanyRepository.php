<?php

namespace App\Repository;

use App\Entity\CsCompany;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsCompany>
 *
 * @method CsCompany|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsCompany|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsCompany[]    findAll()
 * @method CsCompany[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsCompany::class);
    }

//    /**
//     * @return CsCompany[] Returns an array of CsCompany objects
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

//    public function findOneBySomeField($value): ?CsCompany
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
