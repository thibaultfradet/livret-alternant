<?php

namespace App\Controller;

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

    #[Route('/extraction/{student}/pdf', name: 'app_extraction_student_pdf')]
    public function studentPdf(
        User $student,
        UserRepository $userRepository,
        SchoolYearRepository $schoolYearRepository,
        TermsAcceptanceRepository $termsAcceptanceRepository,
        SkillLevelRepository $skillLevelRepository,
        PdfService $pdfService
    ): Response {

        //get active year
        $activeYear = $schoolYearRepository->findActive();
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        //get student full data
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // image path
        $formationCenterImgPath = sprintf('/uploads/general/formation-center-%d.png', $activeYear->getId());
        $ttmImgPath = sprintf('/uploads/classroom/teacher-list-%d.png', $student->getClassroom()->getId());
        $calendarImgPath = sprintf('/uploads/classroom/calendar-%d.png', $student->getClassroom()->getId());

        if ($student->isAlternance()) {
            $termsContent = $activeYear->getTermsContentAlternance();
        } else {
            $termsContent = $activeYear->getTermsContentPro();
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

        // get current year terms acceptance
        $acceptances = [
            'tutor' => null,
            'student' => null,
            'principalTeacher' => null,
        ];

        if ($tutor) {
            $accept = $termsAcceptanceRepository->findOneBy([
                'schoolYear' => $activeYear,
                'user' => $tutor,
            ]);
            $acceptances['tutor'] = $accept ? $accept->getValidationDate() : null;
        }

        //student
        $accept = $termsAcceptanceRepository->findOneBy([
            'schoolYear' => $activeYear,
            'user' => $student,
        ]);
        $acceptances['student'] = $accept ? $accept->getValidationDate() : null;

        // principal teacher
        if ($principalTeacher) {
            $accept = $termsAcceptanceRepository->findOneBy([
                'schoolYear' => $activeYear,
                'user' => $principalTeacher,
            ]);
            $acceptances['principalTeacher'] = $accept ? $accept->getValidationDate() : null;
        }

        $allSkillsLevels = $skillLevelRepository->findBy([
            'disabledAt' => null,
        ]);

        // generate html
        $html = $this->renderView('extraction/pdf_student.html.twig', [
            'controller_name' => 'Extraction',
            'student' => $student,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
            'formationCenterPath' => $formationCenterImgPath,
            'ttmPath' => $ttmImgPath,
            'calendarPath' => $calendarImgPath,
            'termsContent' => $termsContent,
            'isPdf' => true, 
            'acceptances' => $acceptances,
            'tutor' => $tutor,
            'principalTeacher' => $principalTeacher,
            'allSkillsLevels' => $allSkillsLevels,
        ]);

        // Génération du PDF
        $pdfService->generatePdf($html, sprintf('livret_%s.pdf', $student->getLastName()));

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
        $activeYear = $schoolYearRepository->findActive();
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        // Retrieve student with all data for the active year
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // Access check
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

        $principalTeacher = $student->getClassroom() ? $student->getClassroom()->getPrincipalTeacher() : null;

        $hasAccess = $user === $student
                    || ($principalTeacher && $user === $principalTeacher)
                    || ($tutor && $user === $tutor)
                    || in_array('ROLE_TTM', $user->getRoles(), true);

        if (!$hasAccess) {
            return $this->redirectToRoute('app_home');
        }

        // Get the selected period from URL query, default to first period
        $periodId = $request->query->get('period');
        $selectedPeriod = null;
        $periods = $student->getClassroom()->getSchoolYear()->getPeriods()->toArray();
        usort($periods, fn($a, $b) => $a->getPeriodNumber() <=> $b->getPeriodNumber()); // sort chronologically

        if ($periodId) {
            foreach ($periods as $p) {
                if ($p->getId() == $periodId) {
                    $selectedPeriod = $p;
                    break;
                }
            }
        }
        // default to first period if none selected
        if (!$selectedPeriod && !empty($periods)) {
            $selectedPeriod = $periods[0];
        }

        // Fetch evaluations grouped by period
        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear, $student);

        return $this->render('extraction/student_evaluations.html.twig', [
            'student' => $student,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
            'allSkillsLevels' => $skillLevelRepository->findBy(['disabledAt' => null]),
            'selectedPeriod' => $selectedPeriod,
            'periods' => $periods, // pass all periods for the dropdown
        ]);
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
