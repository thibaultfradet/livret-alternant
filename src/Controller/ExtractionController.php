<?php

namespace App\Controller;

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
        SchoolYearRepository $schoolYearRepository,
        PdfService $pdfService
    ): Response {

        // Get the active school year
        $activeYear = $schoolYearRepository->findActive();
        if (!$activeYear) {
            throw $this->createNotFoundException('No active school year found.');
        }

        // Get the student with all related data
        $student = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
      

        // student not found throw
        if (!$student) {
            throw $this->createNotFoundException('Student not found.');
        }

        // format ttm roles
        $ttmRoles = [];
        foreach ($student->getClassroom()->getTtmClassrooms() as $ttmClassroom) {
            $ttm = $ttmClassroom->getTtm();
            $ttmId = $ttm->getId();

            if (!isset($ttmRoles[$ttmId])) {
                $ttmRoles[$ttmId] = [
                    'name' => $ttm->getFirstName() . ' ' . $ttm->getLastName(),
                    'roles' => [],
                ];
            }
            $ttmRoles[$ttmId]['roles'][] = $ttmClassroom->getLabel();
        }



        // format skill evaluation
        $skillEvaluationsByPeriod = [];
        $activeYearId = $activeYear->getId(); 

        foreach ($student->getTutorEvaluationsReceived() as $tutorEvaluation) {
            $period = $tutorEvaluation->getPeriod();
            if (!$period) {
                continue; // Skip if no period
            }

            // Filter by active school year via the period
            $periodYear = $period->getSchoolYear(); // Assuming Period entity has getSchoolYear()
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



                



        // Load CSS files
        $path = $this->getParameter('kernel.project_dir') . '/public/assets/styles/';
        $bootstrap = file_get_contents($path . "bootstrap-5.3.8.min.css");
        $css = file_get_contents($path . "app.css");

        // === Build HTML manually with inline CSS for PDF ===
        $html = '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Extraction PDF</title>
            <style>' . $bootstrap . '</style>
            <style>' . $css . '</style>
            <style>body { font-family: "DejaVu Sans", sans-serif; }</style>
        </head>
        <body>';

        // Render the main Twig template and append to HTML
        $html .= $this->renderView('extraction/index.html.twig', [
            'controller_name' => 'ExtractionController',
            'student' => $student,
            'ttmRoles' => $ttmRoles,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
        ]);
        $html .= '</body></html>';
        // Generate PDF
        $pdfService->generatePdf($html, 'extraction.pdf');

        return new Response();
    }
}
