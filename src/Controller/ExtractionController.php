<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\SchoolYearRepository;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ExtractionController extends AbstractController
{
    #[Route('/extraction/{student}', name: 'app_extraction')]
    public function index(
        User $student,
        UserRepository $userRepository,
        SchoolYearRepository $schoolYearRepository
    ): Response {

        // Get the active school year
        $activeYear = $schoolYearRepository->findActive();
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        // Get the student with all related data
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());

        // Student not found
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

       
        // formation center path
        $formationCenterImgPath = sprintf(
            '/uploads/general/formation-center-%d.png',
            $activeYear->getId()
        );

        // full teaching team path
        $ttmImgPath = sprintf(
            '/uploads/classroom/teacher-list-%d.png',
            $student->getClassroom()->getId()
        );

        // classroom calendar path
        $calendarImgPath = sprintf(
            '/uploads/classroom/calendar-%d.png',
            $student->getClassroom()->getId()
        );

        $termsContent = $activeYear->getTermsContent();

        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear,$student);

        // Render the main Twig template
        return $this->render('extraction/index.html.twig', [
            'controller_name' => 'Extraction',
            'student' => $student,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
            'formationCenterPath' => $formationCenterImgPath,
            'ttmPath' => $ttmImgPath,
            'calendarPath' => $calendarImgPath,
            'termsContent' => $termsContent,
            ]);
    }


    #[Route('/extraction/{student}/pdf', name: 'app_extraction_student_pdf')]
    public function studentPdf(
        User $student,
        UserRepository $userRepository,
        SchoolYearRepository $schoolYearRepository,
        PdfService $pdfService
    ): Response {

        // Récupération de l'année active
        $activeYear = $schoolYearRepository->findActive();
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        // Récupération des données complètes de l'étudiant
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // Chemins des images
        $formationCenterImgPath = sprintf('/uploads/general/formation-center-%d.png', $activeYear->getId());
        $ttmImgPath = sprintf('/uploads/classroom/teacher-list-%d.png', $student->getClassroom()->getId());
        $calendarImgPath = sprintf('/uploads/classroom/calendar-%d.png', $student->getClassroom()->getId());

        $termsContent = $activeYear->getTermsContent();
        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear, $student);

        // On génère le HTML depuis le même template que la page HTML
        $html = $this->renderView('extraction/pdf_student.html.twig', [
            'controller_name' => 'Extraction',
            'student' => $student,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
            'formationCenterPath' => $formationCenterImgPath,
            'ttmPath' => $ttmImgPath,
            'calendarPath' => $calendarImgPath,
            'termsContent' => $termsContent,
            'isPdf' => true, // optionnel, pour adapter certains styles si besoin
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
                    'level' => $skill->getSkillLevel() ? $skill->getSkillLevel()->getLabel() : null
                ];
            }
        }
        return $skillEvaluationsByPeriod;
    }
}
