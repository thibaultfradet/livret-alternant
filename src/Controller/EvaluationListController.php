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
        Request $request
    ): Response {

        /** @var User $tutor */
        $tutor = $security->getUser();

        $now = new \DateTime();

        // Récupérer les alternants du tuteur avec contrats actifs, dans une année scolaire active.
        // Un tuteur peut suivre des alternants de plusieurs établissements : on agrège tous ses
        // contrats actifs au lieu de se limiter à une seule année « active » globale.
        // Classroom / SchoolYear / Diploma sont hydratés en une seule requête (évite les lazy loads).
        $students = $userRepo->createQueryBuilder('s')
            ->join('s.studentContracts', 'sc')
            ->join('s.classroom', 'c')->addSelect('c')
            ->join('c.schoolYear', 'sy')->addSelect('sy')
            ->join('c.diploma', 'd')->addSelect('d')
            ->where('sy.active = :active')
            ->andWhere('sc.tutor = :tutor')
            // Vérifier que le contrat est actif (date de début <= maintenant ET date de fin >= maintenant)
            ->andWhere('(sc.dateDebutContract IS NULL OR sc.dateDebutContract <= :now)')
            ->andWhere('(sc.dateFinContract IS NULL OR sc.dateFinContract >= :now)')
            ->setParameter('active', true)
            ->setParameter('tutor', $tutor)
            ->setParameter('now', $now)
            ->orderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getResult();

        // Années scolaires (actives) distinctes représentées par ces alternants
        $schoolYearsById = [];
        foreach ($students as $student) {
            $sy = $student->getClassroom()->getSchoolYear();
            $schoolYearsById[$sy->getId()] = $sy;
        }

        // Périodes : toutes les périodes de ces années scolaires, en une seule requête
        $allPeriods = $schoolYearsById
            ? $periodRepo->createQueryBuilder('p')
                ->where('p.schoolYear IN (:years)')
                ->setParameter('years', array_values($schoolYearsById))
                ->orderBy('p.startDate', 'ASC')
                ->getQuery()
                ->getResult()
            : [];
        $allPeriodsById = [];
        foreach ($allPeriods as $p) {
            $allPeriodsById[$p->getId()] = $p;
        }

        // Période sélectionnée, sinon période active si elle concerne le tuteur, sinon la première
        $period = null;
        $periodId = $request->query->get('period');
        if ($periodId) {
            $period = $periodRepo->find($periodId);
        }
        if (!$period) {
            $activePeriod = $periodRepo->getActivePeriod();
            if ($activePeriod && isset($allPeriodsById[$activePeriod->getId()])) {
                $period = $activePeriod;
            }
        }
        if (!$period && !empty($allPeriods)) {
            $period = $allPeriods[0];
        }

        // Construire la liste des diplômes pour le filtre (id => label)
        $diplomas = [];
        foreach ($students as $student) {
            $diploma = $student->getClassroom()->getDiploma();
            $diplomas[$diploma->getId()] = $diploma->getLabel();
        }

        // Filtrer par diplôme si sélectionné
        $diplomaId = $request->query->get('diploma');
        if ($diplomaId) {
            $students = array_filter($students, function($student) use ($diplomaId) {
                return (int)$student->getClassroom()->getDiploma()->getId() === (int)$diplomaId;
            });
        }

        return $this->render('evaluation/list_tutor.html.twig', [
            'students'        => $students,
            'period'          => $period,
            'allPeriods'      => $allPeriods,
            'diplomas'        => $diplomas,
            'selectedDiploma' => $diplomaId,
            'hasPeriods'      => !empty($allPeriods),
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

        /** @var User $currentUser */
        $currentUser = $security->getUser();

        $activeSchoolYear = $schoolYearRepo->findActiveByEstablishment($currentUser->getEstablishment());

        // Récupérer toutes les périodes de l'année
        $allPeriods = $periodRepo->createQueryBuilder('p')
            ->where('p.schoolYear = :schoolYear')
            ->orderBy('p.startDate', 'ASC')
            ->setParameter('schoolYear', $activeSchoolYear)
            ->getQuery()
            ->getResult();

        // période sélectionnée ou période active par défaut
        $periodId = $request->query->get('period');
        $period = null;
        if ($periodId) {
            $period = $periodRepo->find($periodId);
        } else {
            $period = $periodRepo->getActivePeriod();
        }
        
        // Si aucune période n'est trouvée, utiliser la première disponible
        if (!$period && !empty($allPeriods)) {
            $period = $allPeriods[0];
        }

        
        $diplomaId = $request->query->get('diploma');
        $evaluations = [];
        $diplomas = [];

        

        $qb = $userRepo->createQueryBuilder('s')
            ->join('s.classroom', 'c')
            ->join('c.schoolYear', 'sy')
            ->leftJoin('s.tutorEvaluationsReceived', 'te', 'WITH', 'te.period = :period')
            ->leftJoin('s.studentEvaluations', 'se', 'WITH', 'se.period = :period')
            ->andWhere('te.id IS NOT NULL')
            ->andWhere('se.id IS NOT NULL')
            ->andWhere('sy.id = :schoolYear')
            ->andWhere('s.establishment = :establishment')
            ->setParameter('period', $period)
            ->setParameter('schoolYear', $activeSchoolYear)
            ->setParameter('establishment', $currentUser->getEstablishment());


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
            'activeSchoolYear' => $activeSchoolYear,
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
