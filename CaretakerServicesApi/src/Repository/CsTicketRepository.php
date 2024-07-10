<?php

namespace App\Repository;

use App\Entity\CsTicket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsTicket>
 *
 * @method CsTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsTicket[]    findAll()
 * @method CsTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsTicket::class);
    }

//    /**
//     * @return CsTicket[] Returns an array of CsTicket objects
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

//    public function findOneBySomeField($value): ?CsTicket
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
