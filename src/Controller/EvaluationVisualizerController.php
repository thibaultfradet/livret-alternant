<?php

namespace App\Controller;

use App\Repository\ClassroomRepository;
use App\Repository\PeriodRepository;
use App\Repository\StudentEvaluationRepository;
use App\Repository\TutorEvaluationRepository;
use App\Repository\TTMEvaluationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvaluationVisualizerController extends AbstractController
{
    #[Route('/evaluation-visualizer', name: 'evaluation_visualizer')]
    public function index(
        Request $request,
        ClassroomRepository $classroomRepo,
        PeriodRepository $periodRepo,
        StudentEvaluationRepository $studentEvalRepo,
        TutorEvaluationRepository $tutorEvalRepo,
        TTMEvaluationRepository $ttmEvalRepo
    ): Response
    {
        // GET params
        $periodId = $request->query->get('period');
        $classroomId = $request->query->get('classroom');

        // Fetch classrooms
        $classrooms = $classroomRepo->findAll();

        // Fetch periods of active school year
        $periods = $periodRepo->findByActiveSchoolYear();

        // Default selected period
        $selectedPeriod = $periodId ? $periodRepo->find($periodId) : ($periods[0] ?? null);

        // Default selected classroom
        $selectedClassroom = $classroomId ? $classroomRepo->find($classroomId) : null;

        // Build evaluations array
        $evaluations = [];
        foreach ($classrooms as $classroom) {
            if ($selectedClassroom && $classroom->getId() != $selectedClassroom->getId()) {
                continue;
            }

            $students = $classroom->getStudents(); 
            foreach ($students as $student) {
                $tutorEval = $tutorEvalRepo->findOneBy([
                    'student' => $student,
                    'period' => $selectedPeriod
                ]);
                $ttmEval = $ttmEvalRepo->findOneBy([
                    'student' => $student,
                    'period' => $selectedPeriod
                ]);
                $studentEval = $studentEvalRepo->findOneBy([
                    'student' => $student,
                    'period' => $selectedPeriod
                ]);

                $evaluations[] = [
                    'student' => $student,
                    'classroom' => $classroom,
                    'tutor_validated' => (bool)$tutorEval,
                    'student_validated' => (bool)$studentEval,
                    'ttm_validated' => (bool)$ttmEval,
                    'overall_validated' => (bool)($tutorEval && $ttmEval && $studentEval),
                ];
            }
        }

        return $this->render('evaluation_visualizer/index.html.twig', [
            'classrooms' => $classrooms,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'selectedClassroom' => $selectedClassroom,
            'evaluations' => $evaluations,
            'error' => null
        ]);
    }
}
