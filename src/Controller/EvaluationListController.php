<?php

namespace App\Controller;

use App\Entity\StudentEvaluation;
use App\Entity\TutorEvaluation;
use App\Entity\TTMEvaluation;
use App\Entity\User;
use App\Repository\DiplomaRepository;
use App\Repository\PeriodRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationListController extends AbstractController
{
    
    #[Route('/evaluations/tutor', name: 'app_evaluation_list_tutor')]
    public function tutorList(
        Security $security,
        PeriodRepository $periodRepo,
        UserRepository $userRepo,
        SchoolYearRepository $schoolYearRepo,
        Request $request
    ): Response {

        /** @var User $tutor */
        $tutor = $security->getUser();

        // Récupérer l'année scolaire active
        $activeSchoolYear = $schoolYearRepo->findOneBy(['active' => true]);

        // Récupérer toutes les périodes de l'année
        $allPeriods = $periodRepo->createQueryBuilder('p')
            ->where('p.schoolYear = :schoolYear')
            ->orderBy('p.startDate', 'ASC')
            ->setParameter('schoolYear', $activeSchoolYear)
            ->getQuery()
            ->getResult();

        // Période sélectionnée ou période active par défaut
        $periodId = $request->query->get('period');
        $period = $periodId ? $periodRepo->find($periodId) : $periodRepo->getActivePeriod();

        $diplomaId = $request->query->get('diploma');

        $qb = $userRepo->createQueryBuilder('s')
            ->join('s.studentContracts', 'sc')
            ->join('s.classroom', 'c')
            ->join('c.schoolYear', 'sy')
            ->where('sy.id = :schoolYear')
            ->andWhere('sc.tutor = :tutor')
            ->setParameter('schoolYear', $activeSchoolYear)
            ->setParameter('tutor', $tutor);

        if ($diplomaId) {
            $qb->andWhere('c.diploma = :diploma')
            ->setParameter('diploma', $diplomaId);
        }

        $students = $qb->orderBy('s.lastName', 'ASC')->getQuery()->getResult();


        // Construire la liste des diplômes pour le filtre
        $diplomas = [];
        foreach ($students as $student) {
            $diplomas[$student->getClassroom()->getDiploma()->getId()] = $student->getClassroom()->getDiploma()->getLabel();
        }

        return $this->render('evaluation/list_tutor.html.twig', [
            'students'        => $students,
            'allPeriods'      => $allPeriods,
            'period'          => $period,
            'diplomas'        => $diplomas,
            'selectedDiploma' => $diplomaId,
            'activeSchoolYear'=> $activeSchoolYear,
        ]);
    }




    #[Route('/evaluations/ttm', name: 'app_evaluation_list_ttm')]
    public function ttmList(
        Security $security,
        PeriodRepository $periodRepo,
        UserRepository $userRepo,
        SchoolYearRepository $schoolYearRepo,
        DiplomaRepository $diplomaRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {

        $activeSchoolYear = $schoolYearRepo->findActive();

        // période par défaut
        $periodId = $request->query->get('period');
        $period = $periodId ? $periodRepo->find($periodId) : $periodRepo->getActivePeriod();


        $allPeriods = $periodRepo->createQueryBuilder('p')
            ->where('p.schoolYear = :schoolYear')
            ->orderBy('p.startDate', 'ASC')
            ->setParameter('schoolYear', $activeSchoolYear)
            ->getQuery()
            ->getResult();

        
        $diplomaId = $request->query->get('diploma');
        $evaluations = [];
        $diplomas = [];

        

        $qb = $userRepo->createQueryBuilder('s')
            ->join('s.classroom', 'c')
            ->join('c.schoolYear', 'sy') // join the school year
            ->leftJoin('s.tutorEvaluationsReceived', 'te', 'WITH', 'te.period = :period')
            ->leftJoin('s.studentEvaluations', 'se', 'WITH', 'se.period = :period')
            ->andWhere('te.id IS NOT NULL')
            ->andWhere('se.id IS NOT NULL')
            ->andWhere('sy.id = :schoolYear') // filter by the current school year
            ->setParameter('period', $period)
            ->setParameter('schoolYear', $activeSchoolYear); 

        if ($diplomaId) {
            $qb->join('c.diploma', 'd')
            ->andWhere('d.id = :diploma')
            ->setParameter('diploma', $diplomaId);
        }

        $students = $qb->orderBy('s.lastName', 'ASC')
                    ->getQuery()
                    ->getResult();
                    
        foreach ($students as $student) {
            $already = $em->getRepository(TTMEvaluation::class)->findOneBy([
                'student' => $student,
                'period'  => $period,
            ]);

            if (!$already) {
                $evaluations[] = [
                    'label' => $student->getFirstName() . ' ' . $student->getLastName() . "-" . $student->getClassroom()->getDiploma()->getLabel(),
                    'route' => $this->generateUrl('app_create_ttm_evaluation', [
                        'student' => $student->getId(),
                        'period'  => $period->getId(),
                    ]),
                    'diploma' => $student->getClassroom()->getDiploma()->getLabel(),
                ];
            }

        }


        // Fetch diplomas that have at least one classroom in the current school year
        $diplomasData = $diplomaRepo->createQueryBuilder('d')
            ->join('d.classrooms', 'c')                // join classrooms
            ->andWhere('c.schoolYear = :schoolYear')   // filter by school year
            ->setParameter('schoolYear', $activeSchoolYear)
            ->getQuery()
            ->getResult();

        // Transform to id => label for Twig select
        $diplomas = [];
        foreach ($diplomasData as $d) {
            $diplomas[$d->getId()] = $d->getLabel();
        }


        return $this->render('evaluation/list_ttm.html.twig', [
            'evaluations' => $evaluations,
            'allPeriods'  => $allPeriods,
            'period'      => $period,
            'diplomas'    => $diplomas,
            'selectedDiploma' => $diplomaId,
        ]);
    }



    #[Route('/evaluations/student', name: 'app_evaluation_list_student')]
    public function studentList(
        Security $security,
        EntityManagerInterface $em
    ): Response {

        /** @var User $student */
        $student = $security->getUser();

        // Safety checks: user, classroom and school year must exist
        $schoolYear = $student?->getClassroom()?->getSchoolYear();

        if (!$schoolYear || !$schoolYear->isActive()) {
            // If no school year or the year is not active, return empty list
            return $this->render('evaluation/list_student.html.twig', [
                'student' => $student,
                'missingEvaluations' => [],
            ]);
        }

        // Get periods from the student's classroom school year
        $periods = $schoolYear->getPeriods()->toArray();

        // Sort periods by start date
        usort($periods, fn($a, $b) => $a->getStartDate() <=> $b->getStartDate());

        $missingEvaluations = [];

        foreach ($periods as $period) {

            // Check if tutor evaluation exists
            $alreadyTutorEvaluation = $em
                ->getRepository(TutorEvaluation::class)
                ->findOneBy([
                    'student' => $student,
                    'period'  => $period,
                ]);

            // Check if student evaluation exists
            $alreadyStudentEvaluation = $em
                ->getRepository(StudentEvaluation::class)
                ->findOneBy([
                    'student' => $student,
                    'period'  => $period,
                ]);

            // Add period only if tutor evaluated but student has not
            if ($alreadyTutorEvaluation && !$alreadyStudentEvaluation) {
                $missingEvaluations[] = $period;
            }
        }

        return $this->render('evaluation/list_student.html.twig', [
            'student' => $student,
            'missingEvaluations' => $missingEvaluations,
        ]);
    }
}
