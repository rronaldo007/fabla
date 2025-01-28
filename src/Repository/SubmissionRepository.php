<?php

namespace App\Repository;

use App\Entity\Submission;
use App\Entity\Edition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Submission>
 */
class SubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    public function findExistingSubmissionForUserAndEdition(User $user, Edition $edition): ?Submission
    {
        return $this->createQueryBuilder('s')
        ->innerJoin('s.editions', 'e')
        ->innerJoin('s.candidateProfile', 'cp')
        ->innerJoin('cp.userProfile', 'up')
        ->innerJoin('up.user', 'u')
        ->where('e.id = :editionId')
        ->andWhere('u.id = :userId')
        ->setParameter('editionId', $edition->getId())
        ->setParameter('userId', $user->getId())      
        ->getQuery()
        ->getOneOrNullResult();
    }

    //    /**
    //     * @return Submission[] Returns an array of Submission objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Submission
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
