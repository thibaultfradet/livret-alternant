<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ExtractionController extends AbstractController
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

       
        // formation center path
        $formationCenterPath = sprintf('/uploads/general/formation-center-%d.png', $activeYear->getId());

        //full teaching team path
        $ttmPath = sprintf('/uploads/classroom/teacher-list-%d.png', $student->getClassroom()->getId());




        $skillEvaluationsByPeriod = $this->getEvaluationData($activeYear,$student);

        // Render the main Twig template
        return $this->render('extraction_temp/index.html.twig', [
            'controller_name' => 'ExtractionTemp',
            'student' => $student,
            'skillEvaluationsByPeriod' => $skillEvaluationsByPeriod,
            'formationCenterPath' => $formationCenterPath,
            'ttmPath' => $ttmPath,
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
                    'level' => $skill->getSkillLevel() ? $skill->getSkillLevel()->getLabel() : null
                ];
            }
        }
        return $skillEvaluationsByPeriod;
    }
}
