<?php

namespace App\Controller;

use App\Entity\Period;
use App\Entity\SchoolYear;
use App\Entity\SkillLevel;
use App\Entity\User;
use App\Repository\SkillLevelRepository;
use App\Repository\TermsAcceptanceRepository;
use App\Repository\UserRepository;
use App\Repository\SchoolYearRepository;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ExtractionController extends AbstractController
{

    #[Route('/extract/{student}/pdf', name: 'app_extract_livret')]
    public function livretPdf(
        User $student,
        UserRepository $userRepository,
        SchoolYearRepository $schoolYearRepository,
        TermsAcceptanceRepository $termsAcceptanceRepository,
        SkillLevelRepository $skillLevelRepository,
        PdfService $pdfService
    ): Response {

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //get active year
        $activeYear = $schoolYearRepository->findActiveByEstablishment($currentUser->getEstablishment());
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        //get student full data
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // image path
        $ttmImgPath = sprintf('/uploads/classroom/teacher-list-%d.png', $student->getClassroom()->getId());
        $calendarImgPath = sprintf('/uploads/classroom/calendar-%d.png', $student->getClassroom()->getId());

        // cover page paths - check if files exist
        $coverPagePath1 = sprintf('/uploads/covers/cover-image-1-%d.png', $activeYear->getId());
        $coverPagePath2 = sprintf('/uploads/covers/cover-image-2-%d.png', $activeYear->getId());

        $coverFileFullPath1 = $this->getParameter('kernel.project_dir') . '/public' . $coverPagePath1;
        $coverFileFullPath2 = $this->getParameter('kernel.project_dir') . '/public' . $coverPagePath2;
       
        if (!file_exists($coverFileFullPath1)) {
            $coverPagePath1 = null;
        }
        if (!file_exists($coverFileFullPath2)) {
            $coverPagePath2 = null;
        }

        if (in_array('ROLE_STUDENT', $student->getRoles(), true)) {
            if ($student->isApprentissage()) {
                $termsConditions = $student->getClassroom()->getTermsConditionsApprentissage();
            } else {
                $termsConditions = $student->getClassroom()->getTermsConditionsPro();
            }
        } else {
            if ($student->isApprentissage()) {
                $termsConditions = $student->getEstablishment()->getTermsConditionsApprentissage();
            } else {
                $termsConditions = $student->getEstablishment()->getTermsConditionsPro();
            }
        }
        
        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear, $student);


        $tutor = null;
        foreach ($student->getStudentContracts() as $tutorLink) {
            $start = $tutorLink->getDateDebutContract();
            $end = $tutorLink->getDateFinContract();

            if (($start === null || $start <= new \DateTime()) &&
                ($end === null || $end >= new \DateTime())) {
                $tutor = $tutorLink->getTutor();
                break;
            }
        }

        // get training principal teacher 
        $principalTeacher = $student->getClassroom() ? $student->getClassroom()->getPrincipalTeacher() : null;

        // get current year terms acceptance (full objects for signature info)
        $acceptances = [
            'tutor' => null,
            'student' => null,
            'principalTeacher' => null,
        ];

        if ($tutor) {
            $acceptances['tutor'] = $termsAcceptanceRepository->findOneBy([
                'schoolYear' => $activeYear,
                'user' => $tutor,
            ]);
        }

        //student
        $acceptances['student'] = $termsAcceptanceRepository->findOneBy([
            'schoolYear' => $activeYear,
            'user' => $student,
        ]);

        // principal teacher
        if ($principalTeacher) {
            $acceptances['principalTeacher'] = $termsAcceptanceRepository->findOneBy([
                'schoolYear' => $activeYear,
                'user' => $principalTeacher,
            ]);
        }

        $allSkillsLevels = $skillLevelRepository->findBy(
            ['disabledAt' => null],
            ['order_index' => 'ASC']
        );

        // generate html
        $html = $this->renderView('extraction/pdf_student.html.twig', [
            'controller_name' => 'Extraction',
            'student' => $student,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
            'formationCenter' => $student->getEstablishment(),
            'ttmPath' => $ttmImgPath,
            'calendarPath' => $calendarImgPath,
            'coverPagePath1' => $coverPagePath1,
            'coverPagePath2' => $coverPagePath2,
            'termsConditions' => $termsConditions,
            'isPdf' => true,
            'acceptances' => $acceptances,
            'tutor' => $tutor,
            'principalTeacher' => $principalTeacher,
            'allSkillsLevels' => $allSkillsLevels,
        ]);

        // Generate filename with comprehensive information
        $today = (new \DateTime())->format('Y-m-d');
        $classCode = $student->getClassroom()->getDiploma()->getCode() ?? 'NOCODE';
        $schoolYearLabel = str_replace('/', '-', $activeYear->getLabel()); // Replace slashes to avoid path issues
        $filename = sprintf(
            'livret_%s-%s_%s_%s_%s.pdf',
            $student->getLastName(),
            $student->getFirstName(),
            $classCode,
            $schoolYearLabel,
            $today
        );

        // Génération du PDF
        $pdfService->generatePdf($html, $filename);

        // Dompdf envoie déjà le PDF, donc on retourne une Response vide
        return new Response();
    }


    #[Route('/student/{student}/evaluations', name: 'app_student_evaluations')]
    public function studentEvaluations(
        User $student,
        UserRepository $userRepository,
        SkillLevelRepository $skillLevelRepository,
        SchoolYearRepository $schoolYearRepository,
        Request $request
    ): Response {

        $user = $this->getUser();
        /** @var User $user */

        // Get active school year
        $activeYear = $schoolYearRepository->findActiveByEstablishment($user->getEstablishment());
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        // Retrieve student with all data for the active year
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // Access control
        $tutor = null;
        foreach ($student->getStudentContracts() as $contract) {
            $start = $contract->getDateDebutContract();
            $end = $contract->getDateFinContract();

            if (($start === null || $start <= new \DateTime()) &&
                ($end === null || $end >= new \DateTime())) {
                $tutor = $contract->getTutor();
                break;
            }
        }

        $principalTeacher = $student->getClassroom()?->getPrincipalTeacher();

        $hasAccess =
            $user === $student ||
            ($principalTeacher && $user === $principalTeacher) ||
            ($tutor && $user === $tutor) ||
            in_array('ROLE_TTM', $user->getRoles(), true);

        if (!$hasAccess) {
            return $this->redirectToRoute('app_home');
        }

        // Retrieve periods for dropdown
        $periods = $student->getClassroom()->getSchoolYear()->getPeriods()->toArray();
        usort($periods, fn ($a, $b) => $a->getPeriodNumber() <=> $b->getPeriodNumber());

        // Get selected period from query
        $periodId = $request->query->get('period');
        $selectedPeriod = null;

        if ($periodId) {
            foreach ($periods as $period) {
                if ($period->getId() == $periodId) {
                    $selectedPeriod = $period;
                    break;
                }
            }
        }

        // Default to first period if none selected
        if (!$selectedPeriod && !empty($periods)) {
            $selectedPeriod = $periods[0];
        }

        // Fetch all evaluations grouped by period
        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear, $student);

        // Filter evaluations to only the selected period
        $filteredSkillEvaluationsByPeriod = [];
        if ($selectedPeriod) {
            $periodNumber = $selectedPeriod->getPeriodNumber();
            if (isset($skillEvaluationsByPeriod[$periodNumber])) {
                $filteredSkillEvaluationsByPeriod[$periodNumber] = $skillEvaluationsByPeriod[$periodNumber];
            }
        }

        return $this->render('extraction/student_evaluations.html.twig', [
            'student' => $student,
            'skillEvaluationsByPeriod' => $filteredSkillEvaluationsByPeriod,
            'allSkillsLevels' => $skillLevelRepository->findBy(['disabledAt' => null], ['order_index' => 'ASC']),
            'selectedPeriod' => $selectedPeriod,
            'periods' => $periods,
        ]);
    }


    #[Route('/student/{student}/period/{period}/pdf', name: 'app_student_period_pdf')]
    public function studentPeriodPdf(
        User $student,
        Period $period,
        UserRepository $userRepository,
        SkillLevelRepository $skillLevelRepository,
        SchoolYearRepository $schoolYearRepository,
        PdfService $pdfService
    ): Response {

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Get active school year
        $activeYear = $schoolYearRepository->findActiveByEstablishment($currentUser->getEstablishment());
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        // Retrieve student with all data for the active year
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // Verify period belongs to the student's classroom school year
        if ($period->getSchoolYear()->getId() !== $activeYear->getId()) {
            throw $this->createNotFoundException('Period not found for this school year.');
        }

        // Fetch all evaluations and filter to the selected period
        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear, $student);
        $periodNumber = $period->getPeriodNumber();
        $filteredSkillEvaluationsByPeriod = [];
        if (isset($skillEvaluationsByPeriod[$periodNumber])) {
            $filteredSkillEvaluationsByPeriod[$periodNumber] = $skillEvaluationsByPeriod[$periodNumber];
        }

        $allSkillsLevels = $skillLevelRepository->findBy(
            ['disabledAt' => null],
            ['order_index' => 'ASC']
        );

        // Generate HTML
        $html = $this->renderView('extraction/pdf_student_period.html.twig', [
            'student' => $student,
            'period' => $period,
            'skillEvaluationsByPeriod' => $filteredSkillEvaluationsByPeriod,
            'allSkillsLevels' => $allSkillsLevels,
            'activeYear' => $activeYear,
        ]);

        // Generate filename
        $today = (new \DateTime())->format('Y-m-d');
        $classCode = $student->getClassroom()->getDiploma()->getCode() ?? 'NOCODE';
        $schoolYearLabel = str_replace('/', '-', $activeYear->getLabel());
        $filename = sprintf(
            'evaluation_%s-%s_%s_P%d_%s_%s.pdf',
            $student->getLastName(),
            $student->getFirstName(),
            $classCode,
            $periodNumber,
            $schoolYearLabel,
            $today
        );

        // Generate PDF
        $pdfService->generatePdf($html, $filename);

        return new Response();
    }

    private function getEvaluationData(SchoolYear $activeYear, User $student)
    {
        // Format skill evaluations
        $skillEvaluationsByPeriod = [];
        $activeYearId = $activeYear->getId(); 

        foreach ($student->getTutorEvaluationsReceived() as $tutorEvaluation) {
            $period = $tutorEvaluation->getPeriod();
            if (!$period) {
                continue; // Skip if no period
            }

            $periodYear = $period->getSchoolYear();
            if (!$periodYear || $periodYear->getId() !== $activeYearId) {
                continue; // Skip periods not in the active year
            }

            foreach ($tutorEvaluation->getSkillEvaluation() as $skill) {
                $skillCriteria = $skill->getSkillCriteria();
                $skillGroup = $skillCriteria->getSkillGroup();

                if (!$skillGroup) {
                    continue; // Skip if no group
                }

                $periodKey = $period->getPeriodNumber();
                $groupId = $skillGroup->getId();

                if (!isset($skillEvaluationsByPeriod[$periodKey])) {
                    $skillEvaluationsByPeriod[$periodKey] = [];
                }

                if (!isset($skillEvaluationsByPeriod[$periodKey][$groupId])) {
                    $skillEvaluationsByPeriod[$periodKey][$groupId] = [
                        'label' => $skillGroup->getLabel(),
                        'criteria' => []
                    ];
                }

                $skillEvaluationsByPeriod[$periodKey][$groupId]['criteria'][] = [
                    'label' => $skillCriteria->getLabel(),
                    'level' => $skill->getSkillLevel()
                ];
            }
        }
        return $skillEvaluationsByPeriod;
    }
}
