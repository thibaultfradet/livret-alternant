<?php

namespace App\Controller;

use App\Entity\Period;
use App\Entity\TutorEvaluation;
use App\Entity\TTMEvaluation;
use App\Entity\User;
use App\Repository\PeriodRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationListController extends AbstractController
{


    #[Route('/api/tutor/students', name: 'api_tutor_students', methods: ['GET'])]
    public function getAllTutorStudents(UserRepository $userRepo, Security $security): JsonResponse
    {
        $tutor = $this->getUser();


        // Récupère tous les étudiants liés au tuteur via tutorContracts
        $students = $tutor->getTutorContracts()->map(fn($ts) => $ts->getStudent())->toArray();

        // Préparer un tableau simple pour JSON (éviter de renvoyer des objets Doctrine)
        $data = array_map(function ($student) {
            return [
                'id' => $student->getId(),
                'firstName' => $student->getFirstName(),
                'lastName' => $student->getLastName(),
                'email' => $student->getEmail(),
                'classroom' => $student->getClassroom()?->getDiploma()->getLabel() ?? null,
            ];
        }, $students);

        return $this->json($data);
    }


    #[Route('/evaluations/tutor', name: 'app_evaluation_list_tutor')]
    public function tutorList(
        Security $security,
        PeriodRepository $periodRepo,
        UserRepository $userRepo,
        SchoolYearRepository $schoolYearRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TUTOR');

        $tutor = $security->getUser();



        // active school year
        $activeSchoolYear = $schoolYearRepo->findOneBy(['active' => true]);

        $allPeriods = $periodRepo->createQueryBuilder('p')
            ->where('p.schoolYear = :schoolYear')
            ->orderBy('p.startDate', 'ASC')
            ->setParameter('schoolYear', $activeSchoolYear)
            ->getQuery()
            ->getResult();


        // choose period or active one as default
        $periodId = $request->query->get('period');
        $period = $periodId ? $periodRepo->find($periodId) : $periodRepo->getActivePeriod();

        $diplomaId = $request->query->get('diploma');
        $evaluations = [];
        $diplomas = [];

        // find students that dont have evaluations already
        $students = $userRepo->createQueryBuilder('s')
            ->join('s.studentContracts', 'ts')
            ->leftJoin('s.tutorEvaluationsReceived', 'te', 'WITH', 'te.period = :period AND te.tutor = :tutor')
            ->where('ts.tutor = :tutor')
            ->andWhere('te.id IS NULL')
            ->setParameter('tutor', $tutor)
            ->setParameter('period', $period)
            ->orderBy('s.lastName', 'ASC')
            ->getQuery()
            ->getResult();



        foreach ($students as $student) {
            $already = $em->getRepository(TutorEvaluation::class)->findOneBy([
                'student' => $student,
                'tutor'   => $tutor,
                'period'  => $period,
            ]);

            if (!$already) {
                $evaluations[] = [
                    'label' => $student->getFirstName() . ' ' . $student->getLastName() . " - " . $student->getClassroom()->getDiploma()->getLabel(),
                    'route' => $this->generateUrl('app_create_evaluation', [
                        'student' => $student->getId(),
                        'period'  => $period->getId(),
                    ]),
                    'diploma' => $student->getClassroom()->getDiploma()->getLabel(),
                ];
            }

            $diplomas[$student->getClassroom()->getDiploma()->getId()] = $student->getClassroom()->getDiploma()->getLabel();
        }

        return $this->render('evaluation/list_tutor.html.twig', [
            'evaluations' => $evaluations,
            'allPeriods'  => $allPeriods,
            'period'      => $period,
            'diplomas'    => $diplomas,
            'selectedDiploma' => $diplomaId,
            'activeSchoolYear' => $activeSchoolYear,
        ]);
    }

    #[Route('/evaluations/ttm', name: 'app_evaluation_list_ttm')]
    public function ttmList(
        Security $security,
        PeriodRepository $periodRepo,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $security->getUser();
        $this->denyAccessUnlessGranted('ROLE_TTM');

        // période par défaut
        $periodId = $request->query->get('period');
        $period = $periodId ? $periodRepo->find($periodId) : $periodRepo->getActivePeriod();

        $diplomaId = $request->query->get('diploma');
        $evaluations = [];
        $diplomas = [];

        // Récupérer les étudiants de mes classes
        $qb = $userRepo->createQueryBuilder('u')
            ->join('u.classroom', 'c')
            ->join('c.ttmClassrooms', 'tc')
            ->join('c.diploma', 'd')
            ->where('tc.ttm = :ttm')
            ->setParameter('ttm', $user);

        if ($diplomaId) {
            $qb->andWhere('d.id = :diploma')->setParameter('diploma', $diplomaId);
        }

        $students = $qb->getQuery()->getResult();

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

            $diplomas[$student->getClassroom()->getDiploma()->getId()] = $student->getClassroom()->getDiploma()->getLabel();
        }

        return $this->render('evaluation/list_ttm.html.twig', [
            'evaluations' => $evaluations,
            'allPeriods'  => $periodRepo->findBy([], ['startDate' => 'ASC']),
            'period'      => $period,
            'diplomas'    => $diplomas,
            'selectedDiploma' => $diplomaId,
        ]);
    }
}
