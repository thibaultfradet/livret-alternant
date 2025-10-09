<?php

namespace App\Repository;

use App\Entity\Classroom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classroom>
 */
class ClassroomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classroom::class);
    }


    public function findByActiveSchoolYear(): array
    {
        // Create query builder for Classroom entity (alias 'c')
        return $this->createQueryBuilder('c')
            ->join('c.schoolYear', 'sy') // Join with the related SchoolYear entity
            ->where('sy.active = :active') // Filter only active school years
            ->setParameter('active', true)
            ->orderBy('c.id', 'ASC') // Order by classroom id
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Classroom[] Returns an array of Classroom objects
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

//    public function findOneBySomeField($value): ?Classroom
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
