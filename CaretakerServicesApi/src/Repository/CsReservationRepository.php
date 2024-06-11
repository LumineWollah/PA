<?php

namespace App\Repository;

use App\Entity\CsApartment;
use App\Entity\CsReservation;
use App\Entity\CsUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CsReservation>
 *
 * @method CsReservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsReservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsReservation[]    findAll()
 * @method CsReservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsReservation::class);
    }

//    /**
//     * @return CsReservation[] Returns an array of CsReservation objects
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

//    public function findOneBySomeField($value): ?CsReservation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findNotAvailableDates(\DateTimeInterface $startingDate, \DateTimeInterface $endingDate)
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.apartment', 'a')
            ->andWhere('o.startingDate < :endingDate AND o.endingDate > :startingDate AND o.apartment IS NOT NULL')
            ->setParameter('startingDate', $startingDate)
            ->setParameter('endingDate', $endingDate)
            ->getQuery()
            ->getResult();
    }

    public function findReservationsForApartment(\DateTimeInterface $startingDate, \DateTimeInterface $endingDate, CsApartment $apartment)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.startingDate < :endingDate AND o.endingDate > :startingDate AND o.apartment = :apartment')
            ->setParameter('startingDate', $startingDate)
            ->setParameter('endingDate', $endingDate)
            ->setParameter('apartment', $apartment)
            ->getQuery()
            ->getResult();
    }

    public function getPastReserv(\DateTimeInterface $todayDate, CsUser $user)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.endingDate < :todayDate AND o.user = :user AND o.apartment IS NOT NULL')
            ->setParameter('todayDate', $todayDate)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getPresentReserv(\DateTimeInterface $todayDate, CsUser $user)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.startingDate < :todayDate AND o.endingDate > :todayDate AND o.user = :user AND o.apartment IS NOT NULL')
            ->setParameter('todayDate', $todayDate)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function getFutureReserv(\DateTimeInterface $todayDate, CsUser $user)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.startingDate > :todayDate AND o.user = :user AND o.apartment IS NOT NULL')
            ->setParameter('todayDate', $todayDate)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}



