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

        $termsContent = $activeYear->getTermsContent();
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
