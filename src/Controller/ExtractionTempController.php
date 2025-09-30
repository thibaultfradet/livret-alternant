<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ExtractionTempController extends AbstractController
{
    #[Route('/extraction/temp/{student}', name: 'app_extraction_temp')]
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

        // Format TTM roles
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

        // Render the main Twig template
        return $this->render('extraction_temp/index.html.twig', [
            'controller_name' => 'ExtractionTemp',
            'student' => $student,
            'ttmRoles' => $ttmRoles,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
        ]);
    }
}
