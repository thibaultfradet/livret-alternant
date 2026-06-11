<?php

namespace App\Repository;

use App\Entity\Establishment;
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



    public function findUsersForEstablishment(Establishment $establishment): array
    {
        $direct = $this->createQueryBuilder('u')
            ->where('u.establishment = :establishment')
            ->andWhere('u.disabledAt IS NULL')
            ->setParameter('establishment', $establishment)
            ->getQuery()
            ->getResult();

        $tutors = $this->createQueryBuilder('u')
            ->join('u.tutorContracts', 'tc')
            ->join('tc.student', 's')
            ->join('s.classroom', 'cl')
            ->join('cl.schoolYear', 'sy')
            ->where('sy.establishment = :establishment')
            ->andWhere('u.disabledAt IS NULL')
            ->setParameter('establishment', $establishment)
            ->distinct()
            ->getQuery()
            ->getResult();

        $ids = array_map(fn(User $u) => $u->getId(), $direct);
        foreach ($tutors as $tutor) {
            if (!\in_array($tutor->getId(), $ids, true)) {
                $direct[] = $tutor;
                $ids[] = $tutor->getId();
            }
        }

        usort($direct, fn(User $a, User $b) => strcmp($a->getLastName(), $b->getLastName()));

        return $direct;
    }

    public function findUsersExcludingOnlyTutor(): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_LENGTH(u.roles) = 0 OR JSON_CONTAINS(u.roles, :role) = 0 OR JSON_LENGTH(u.roles) > 1')
            ->setParameter('role', json_encode('ROLE_TUTOR'))
            ->getQuery()
            ->getResult();
    }


    /**
     * Retrieve a student with all related data for the active school year.
     */
    public function findStudentWithAllData(int $studentId, int $activeSchoolYearId): ?User
    {
        $qb = $this->createQueryBuilder('u');

        $qb->leftJoin('u.studentContracts', 'ts')
            ->addSelect('ts')
            ->leftJoin('ts.tutor', 'tutor')
            ->addSelect('tutor');

        $qb->leftJoin(
                'u.tutorEvaluationsReceived',
                'te',
                'WITH',
                'te.period IN (
                    SELECT p1.id FROM App\Entity\Period p1
                    WHERE p1.schoolYear = :activeSchoolYearId
                )'
            )
            ->addSelect('te')
            ->leftJoin('te.skillEvaluation', 'tes')
            ->addSelect('tes')
            ->leftJoin('te.behaviorEvaluation', 'teb')
            ->addSelect('teb');

        $qb->leftJoin(
                'u.studentEvaluations',
                'se',
                'WITH',
                'se.period IN (
                    SELECT p2.id FROM App\Entity\Period p2
                    WHERE p2.schoolYear = :activeSchoolYearId
                )'
            )
            ->addSelect('se');

        $qb->leftJoin(
                'u.ttmEvaluationsReceived',
                'tte',
                'WITH',
                'tte.period IN (
                    SELECT p3.id FROM App\Entity\Period p3
                    WHERE p3.schoolYear = :activeSchoolYearId
                )'
            )
            ->addSelect('tte');

        $qb->leftJoin(
                'u.termsAcceptances',
                'ta',
                'WITH',
                'ta.schoolYear = :activeSchoolYearId'
            )
            ->addSelect('ta');

        $qb->where('u.id = :studentId')
            ->setParameter('studentId', $studentId)
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
