<?php

namespace App\Repository;

use App\Entity\CsUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<CsUser>
 *
 * @method CsUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsUser[]    findAll()
 * @method CsUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsUser::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof CsUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

//    /**
//     * @return CsUser[] Returns an array of CsUser objects
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

//    public function findOneBySomeField($value): ?CsUser
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function userExistsByEmail(string $email): bool
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('COUNT(u.id)');
        $qb->andWhere('u.email = :email');
        $qb->setParameter('email', $email);
        
        $count = $qb->getQuery()->getSingleScalarResult();
        
        return $count > 0;
    }
}
