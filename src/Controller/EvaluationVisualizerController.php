<?php

namespace App\Controller;

use App\Entity\Period;
use App\Form\NotifierEvaluationType;
use App\Repository\ClassroomRepository;
use App\Repository\PeriodRepository;
use App\Repository\StudentEvaluationRepository;
use App\Repository\TutorEvaluationRepository;
use App\Repository\TTMEvaluationRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;    
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;



class EvaluationVisualizerController extends AbstractController
{
    #[Route('/evaluation-visualizer', name: 'app_evaluation_visualizer')]
    public function index(
        Request $request,
        ClassroomRepository $classroomRepo,
        PeriodRepository $periodRepo,
        StudentEvaluationRepository $studentEvalRepo,
        TutorEvaluationRepository $tutorEvalRepo,
        TTMEvaluationRepository $ttmEvalRepo
    ): Response {
        // Récupère données communes
        $data = $this->getEvaluationData(
            $request,
            $classroomRepo,
            $periodRepo,
            $studentEvalRepo,
            $tutorEvalRepo,
            $ttmEvalRepo,
            $request->query->get('classroom')

        );

        return $this->render('evaluation_visualizer/index.html.twig', $data);
    }

    #[Route('/export-evaluations', name: 'export_evaluations')]
    public function exportEvaluations(
        Request $request,
        ClassroomRepository $classroomRepo,
        PeriodRepository $periodRepo,
        StudentEvaluationRepository $studentEvalRepo,
        TutorEvaluationRepository $tutorEvalRepo,
        TTMEvaluationRepository $ttmEvalRepo
    ): Response {
        // Récupère données communes
        $data = $this->getEvaluationData(
            $request,
            $classroomRepo,
            $periodRepo,
            $studentEvalRepo,
            $tutorEvalRepo,
            $ttmEvalRepo,
            $request->query->get('classroom')

        );

        $evaluations = $data['evaluations'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Titres
        $sheet->setCellValue('A1', 'Alternant');
        $sheet->setCellValue('B1', 'Évaluation tuteur');
        $sheet->setCellValue('C1', 'Évaluation alternant');
        $sheet->setCellValue('D1', 'Évaluation de l\'équipe pédagogique');

        // Données
        $row = 2;
        foreach ($evaluations as $eval) {
            $sheet->setCellValue(
                'A'.$row,
                $eval['student']->getFirstName() . ' ' . $eval['student']->getLastName()
                . ' (' . $eval['classroom']->getDiploma()->getLabel()
                . ', ' . $eval['classroom']->getSchoolYear()->getLabel() . ')'
            );
            $sheet->setCellValue('B'.$row, $eval['tutor_validated'] ? '✔' : '✘');
            $sheet->setCellValue('C'.$row, $eval['student_validated'] ? '✔' : '✘');
            $sheet->setCellValue('D'.$row, $eval['ttm_validated'] ? '✔' : '✘');
            $row++;
        }

        foreach (range('A','D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $selectedPeriod = $data['selectedPeriod'];
        $selectedClassroom = $data['selectedClassroom'];
        $writer = new Xlsx($spreadsheet);
        $date = (new \DateTime())->format('Y-m-d');
        $classroomLabel = $selectedClassroom
        ? preg_replace('/[^a-zA-Z0-9-_]/', '_', $selectedClassroom->getDiploma()->getLabel())
        : 'Toutes_Classes';
        $periodLabel = $selectedPeriod
        ? 'Periode' . $selectedPeriod->getPeriodNumber() . '_' . $selectedPeriod->getStartDate()->format('Y')
        : 'Toutes_Periods';

        $fileName = sprintf('Evaluations_%s_%s_%s.xlsx', $classroomLabel, $periodLabel, $date);

        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return $this->file($tempFile, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    

    // return the data based on the request (get params) to filter with period and classroom
  private function getEvaluationData(
        Request $request,
        ClassroomRepository $classroomRepo,
        PeriodRepository $periodRepo,
        StudentEvaluationRepository $studentEvalRepo,
        TutorEvaluationRepository $tutorEvalRepo,
        TTMEvaluationRepository $ttmEvalRepo,
        string $classroomId = null
    ): array {
        $periodId = $request->query->get('period');
        $user = $this->getUser(); 

        $classrooms = $classroomRepo->findByActiveSchoolYear();
        $periods = $periodRepo->findByActiveSchoolYear();

        $selectedPeriod = $periodId ? $periodRepo->find($periodId) : ($periods[0] ?? null);
        $selectedClassroom = $classroomId ? $classroomRepo->find($classroomId) : null;

        // Check user's roles
        $roles = $user->getRoles(); // array of roles
        $isPtOnly = in_array('ROLE_PT', $roles) && count(array_diff($roles, ['ROLE_USER', 'ROLE_PT'])) === 0;

        // Filter classrooms if needed
        if ($isPtOnly) {
            $classrooms = array_filter($classrooms, fn($classroom) => $classroom->getPrincipalTeacher() === $user);
        }

        $evaluations = [];
        foreach ($classrooms as $classroom) {
            if ($selectedClassroom && $classroom->getId() !== $selectedClassroom->getId()) {
                continue;
            }
            foreach ($classroom->getStudents() as $student) {
                $tutorEval = $tutorEvalRepo->findOneBy(['student' => $student, 'period' => $selectedPeriod]);
                $ttmEval = $ttmEvalRepo->findOneBy(['student' => $student, 'period' => $selectedPeriod]);
                $studentEval = $studentEvalRepo->findOneBy(['student' => $student, 'period' => $selectedPeriod]);

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

        return [
            'classrooms' => $classrooms,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'selectedClassroom' => $selectedClassroom,
            'evaluations' => $evaluations,
            'error' => null,
        ];
    }



    

    #[Route('/evaluation-visualizer/notifier', name: 'notifier_pending_evaluations')]
    public function notifier(
        Request $request,
        ClassroomRepository $classroomRepo,
        PeriodRepository $periodRepo,
        StudentEvaluationRepository $studentEvalRepo,
        TutorEvaluationRepository $tutorEvalRepo,
        TTMEvaluationRepository $ttmEvalRepo,
        MailerInterface $mailer
    ): Response {
        // get evaluation data
        $data = $this->getEvaluationData(
            $request,
            $classroomRepo,
            $periodRepo,
            $studentEvalRepo,
            $tutorEvalRepo,
            $ttmEvalRepo
        );

        // only keep the non evaluate 
        $pendingEvaluations = array_filter($data['evaluations'], fn($eval) => !$eval['tutor_validated'] || !$eval['student_validated']);
        $students = array_map(fn($eval) => $eval['student'], $pendingEvaluations);

        // create the form
        $form = $this->createForm(NotifierEvaluationType::class, null, [
            'students' => $students,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User[] $selectedStudents */
            $selectedStudents = $form->get('selectedStudents')->getData();
            $periodId = $form->get('periodId')->getData();

            // filter evaluation baseed on selected user
            $selectedEvaluations = array_filter(
                $pendingEvaluations,
                fn($eval) => in_array($eval['student'], $selectedStudents, true) 
            );

            $period = $periodRepo->find($periodId);

            $this->sendNotification($selectedEvaluations,$period,$mailer);

            $this->addFlash('success', count($selectedEvaluations) . ' notifications envoyées.');
            return $this->redirectToRoute('notifier_pending_evaluations', [
                'period' => $periodId,
            ]);
        }

        return $this->render('evaluation_visualizer/notifier.html.twig', [
            'form' => $form->createView(),
            'evaluations' => $pendingEvaluations,
            'selectedPeriod' => $data['selectedPeriod'],
        ]);
    }

    private function sendNotification(array $pendingEvaluations, Period $period, MailerInterface $mailer): void
    {
        if (empty($pendingEvaluations)) {
            return;
        }

        foreach ($pendingEvaluations as $eval) {
            $student = $eval['student'];
            $classroom = $eval['classroom'];
            $periodLabel = $period->getPeriodNumber();


            $tutorContract = $student->getStudentContracts()->first();
            if (!$eval['tutor_validated'] && $tutorContract && $tutorContract->getTutor()) {
                $user = $tutorContract->getTutor(); 
                $badge = 'Tuteur';
            } else {
                $user = $student; 
                $badge = 'Alternant';
            }

            // sécurisation
            if (!$user || !$user->getEmail()) {
                continue;
            }

            // Build the email
            $email = (new TemplatedEmail())
                ->from(new Address('test@example.com', 'Livret de l\'alternant'))
                ->to($user->getEmail())
                ->subject("Livret de l'alternant - Validation d'évaluation en attente")
                ->htmlTemplate('evaluation_visualizer/email_reminder.html.twig')
                ->context([
                    'student'   => $student,
                    'classroom' => $classroom,
                    'badge'     => $badge,
                    'period'    => $periodLabel,
                ]);

            $mailer->send($email);
        }
    }
}