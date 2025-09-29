<?php

namespace App\Repository;

use App\Entity\Period;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Period>
 */
class PeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Period::class);
    }

    public function getActivePeriod(): ?Period
    {
        $now = new \DateTimeImmutable();
        return $this->createQueryBuilder('p')
            ->where('p.endDate >= :now')
            ->setParameter('now', $now)
            ->orderBy('p.startDate', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }



    public function findByActiveSchoolYear(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.schoolYear', 'sy')
            ->where('sy.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.periodNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Period[] Returns an array of Period objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Period
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
