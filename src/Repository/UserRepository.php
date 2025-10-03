<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Retrieve a student with all related data for the active school year.
     */
    public function findStudentWithAllData(int $studentId, int $activeSchoolYearId): ?User
    {
        $qb = $this->createQueryBuilder('u');

        // Tutor contract
        $qb->leftJoin('u.studentContracts', 'ts')
            ->addSelect('ts')
            ->leftJoin('ts.tutor', 'tutor')
            ->addSelect('tutor');


        // Tutor Evaluations received
        $qb->leftJoin('u.tutorEvaluationsReceived', 'te')
            ->addSelect('te')
            ->leftJoin('te.skillEvaluation', 'tes')
            ->addSelect('tes')
            ->leftJoin('te.behaviorEvaluation', 'teb')
            ->addSelect('teb')
            ->leftJoin('te.period', 'tp')
            ->addSelect('tp')
            ->leftJoin('tp.schoolYear', 'sy_te')
            ->addSelect('sy_te')
            ->andWhere('sy_te.id = :activeSchoolYearId');

        // Student Evaluations
        $qb->leftJoin('u.studentEvaluations', 'se')
            ->addSelect('se')
            ->leftJoin('se.period', 'sp')
            ->addSelect('sp')
            ->leftJoin('sp.schoolYear', 'sy_se')
            ->addSelect('sy_se')
            ->andWhere('sy_se.id = :activeSchoolYearId');

        // TTMEvaluations
        $qb->leftJoin('u.ttmEvaluationsReceived', 'tte')
            ->addSelect('tte')
            ->leftJoin('tte.period', 'tp_ttm')
            ->addSelect('tp_ttm')
            ->leftJoin('tp_ttm.schoolYear', 'sy_ttm')
            ->addSelect('sy_ttm')
            ->andWhere('sy_ttm.id = :activeSchoolYearId');

        // Terms acceptance
        $qb->leftJoin('u.termsAcceptances', 'ta')
            ->addSelect('ta')
            ->leftJoin('ta.schoolYear', 'sy_ta')
            ->addSelect('sy_ta')
            ->andWhere('sy_ta.id = :activeSchoolYearId');

        $qb->where('u.id = :studentId')
            ->setParameter('studentId', $studentId)
            ->andWhere('(sy_te.id = :activeSchoolYearId OR sy_se.id = :activeSchoolYearId OR sy_ttm.id = :activeSchoolYearId OR sy_ta.id = :activeSchoolYearId)')
            ->setParameter('activeSchoolYearId', $activeSchoolYearId);

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
