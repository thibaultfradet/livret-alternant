<?php

namespace App\Controller;

use App\Entity\Period;
use App\Entity\User;
use App\Entity\SchoolYear;
use App\Form\NotifierEvaluationType;
use App\Form\ExtractorEvaluationType;
use App\Repository\ClassroomRepository;
use App\Repository\PeriodRepository;
use App\Repository\StudentEvaluationRepository;
use App\Repository\TutorEvaluationRepository;
use App\Repository\TTMEvaluationRepository;
use App\Repository\UserRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SkillLevelRepository;
use App\Repository\TermsAcceptanceRepository;
use App\Service\PdfService;
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
        ?string $classroomId = null
    ): array {
        $periodId = $request->query->get('period');
        $user = $this->getUser();
        $userEstablishment = $user->getEstablishment();
        
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

                // Skip students not belonging to the current user's establishment
                if (
                    $userEstablishment !== null &&
                    $student->getEstablishment() !== $userEstablishment
                ) {
                    continue;
                }
            
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
                    'tutor_validated' => (bool) $tutorEval,
                    'student_validated' => (bool) $studentEval,
                    'ttm_validated' => (bool) $ttmEval,
                    'overall_validated' => (bool) ($tutorEval && $ttmEval && $studentEval),
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
            'periods' => $data['periods'],
            'selectedPeriod' => $data['selectedPeriod'],
        ]);
    }

    #[Route('/evaluation-visualizer/extract', name: 'app_extract_period')]
    public function extractPeriod(
        Request $request,
        ClassroomRepository $classroomRepo,
        PeriodRepository $periodRepo,
        StudentEvaluationRepository $studentEvalRepo,
        TutorEvaluationRepository $tutorEvalRepo,
        TTMEvaluationRepository $ttmEvalRepo,
        UserRepository $userRepository,
        SchoolYearRepository $schoolYearRepository,
        SkillLevelRepository $skillLevelRepository,
        TermsAcceptanceRepository $termsAcceptanceRepository,
        PdfService $pdfService
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $establishment = $currentUser->getEstablishment();

        // get evaluation data
        $data = $this->getEvaluationData(
            $request,
            $classroomRepo,
            $periodRepo,
            $studentEvalRepo,
            $tutorEvalRepo,
            $ttmEvalRepo
        );

        // Filter periods by user's establishment
        $periods = array_filter($data['periods'], fn($p) => $p->getSchoolYear()->getEstablishment() === $establishment);
        $selectedPeriod = $data['selectedPeriod'];
        if ($selectedPeriod && $selectedPeriod->getSchoolYear()->getEstablishment() !== $establishment) {
            $selectedPeriod = $periods[0] ?? null;
        }

        $hasPeriods = !empty($periods);

        // get all students from classrooms
        $allStudents = [];
        foreach ($data['classrooms'] as $classroom) {
            foreach ($classroom->getStudents() as $student) {
                // Skip students not belonging to the current user's establishment
                if (
                    $establishment !== null &&
                    $student->getEstablishment() !== $establishment
                ) {
                    continue;
                }
                $allStudents[] = $student;
            }
        }

        // Create the form
        $form = $this->createForm(ExtractorEvaluationType::class, null, [
            'students' => $allStudents,
        ]);
        $form->handleRequest($request);

        if ($hasPeriods && $form->isSubmitted() && $form->isValid()) {
            /** @var User[] $selectedStudents */
            $selectedStudents = $form->get('selectedStudents')->getData();
            $periodId = $form->get('periodId')->getData();

            if (empty($selectedStudents)) {
                $this->addFlash('warning', 'Aucun alternant sélectionné.');
                return $this->redirectToRoute('app_extract_period', [
                    'period' => $periodId,
                ]);
            }

            $period = $periodRepo->find($periodId);
            if (!$period) {
                $this->addFlash('warning', 'Période non trouvée.');
                return $this->redirectToRoute('app_extract_period');
            }

            // Generate archive with PDFs
            $archiveResponse = $this->generateStudentsPdfArchive(
                $selectedStudents,
                $period,
                $userRepository,
                $schoolYearRepository,
                $skillLevelRepository,
                $termsAcceptanceRepository,
                $pdfService
            );

            if ($archiveResponse === null) {
                return $this->redirectToRoute('app_extract_period', ['period' => $periodId]);
            }

            return $archiveResponse;
        }

        return $this->render('evaluation_visualizer/extract.html.twig', [
            'form' => $form->createView(),
            'students' => $allStudents,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'classrooms' => $data['classrooms'],
            'hasPeriods' => $hasPeriods,
        ]);
    }

    /**
     * Generate a ZIP archive containing PDFs for selected students for a specific period
     */
    private function generateStudentsPdfArchive(
        array $selectedStudents,
        Period $period,
        UserRepository $userRepository,
        SchoolYearRepository $schoolYearRepository,
        SkillLevelRepository $skillLevelRepository,
        TermsAcceptanceRepository $termsAcceptanceRepository,
        PdfService $pdfService
    ): ?Response {
        $currentUser = $this->getUser();
        $activeYear = $schoolYearRepository->findActiveByEstablishment($currentUser->getEstablishment());
        if (!$activeYear) {
            $this->addFlash('error', 'Aucune année scolaire active trouvée.');
            return null;
        }

        // Verify period belongs to the active school year
        if ($period->getSchoolYear()->getId() !== $activeYear->getId()) {
            $this->addFlash('error', 'La période sélectionnée ne correspond pas à l\'année scolaire active.');
            return null;
        }

        // Create temp directory for PDFs
        $tempDir = sys_get_temp_dir() . '/extraction_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            $this->addFlash('error', 'Impossible de créer le répertoire temporaire pour les fichiers.');
            return null;
        }

        $pdfFiles = [];
        $allSkillsLevels = $skillLevelRepository->findBy(
            ['disabledAt' => null],
            ['order_index' => 'ASC']
        );

        try {
            // Generate PDFs for each selected student
            foreach ($selectedStudents as $student) {
                // Retrieve student with all data for the active year
                $fullStudent = $userRepository->findStudentWithAllData($student->getId(), $activeYear->getId());
                if (!$fullStudent) {
                    continue; // Skip if student data cannot be retrieved
                }

                // Fetch all evaluations and filter to the selected period
                $skillEvaluationsByPeriod = $this->getStudentEvaluationData($activeYear, $fullStudent);
                $periodNumber = $period->getPeriodNumber();
                $filteredSkillEvaluationsByPeriod = [];
                if (isset($skillEvaluationsByPeriod[$periodNumber])) {
                    $filteredSkillEvaluationsByPeriod[$periodNumber] = $skillEvaluationsByPeriod[$periodNumber];
                }

                // Generate HTML for PDF
                $html = $this->renderView('extraction/pdf_student_period.html.twig', [
                    'student' => $fullStudent,
                    'period' => $period,
                    'skillEvaluationsByPeriod' => $filteredSkillEvaluationsByPeriod,
                    'allSkillsLevels' => $allSkillsLevels,
                    'activeYear' => $activeYear,
                ]);

                // Generate filename
                $today = (new \DateTime())->format('Y-m-d');
                $classCode = $fullStudent->getClassroom()->getDiploma()->getCode() ?? 'NOCODE';
                $schoolYearLabel = str_replace('/', '-', $activeYear->getLabel());
                $filename = sprintf(
                    'evaluation_%s-%s_%s_P%d_%s_%s.pdf',
                    $fullStudent->getLastName(),
                    $fullStudent->getFirstName(),
                    $classCode,
                    $periodNumber,
                    $schoolYearLabel,
                    $today
                );

                // Generate PDF directly to file
                $pdfFilePath = $tempDir . '/' . $filename;
                $pdfService->generatePdfToFile($html, $pdfFilePath);
                $pdfFiles[] = [
                    'path' => $pdfFilePath,
                    'name' => $filename
                ];
            }

            if (empty($pdfFiles)) {
                $this->addFlash('error', 'Aucun PDF n\'a pu être généré pour les alternants sélectionnés.');
                $this->deleteDirectory($tempDir);
                return null;
            }

            // Create ZIP archive
            $zipPath = sys_get_temp_dir() . '/extractions_' . date('Y-m-d_H-i-s') . '.zip';
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                $this->addFlash('error', 'Impossible de créer l\'archive ZIP.');
                $this->deleteDirectory($tempDir);
                return null;
            }

            // Add PDFs to archive
            foreach ($pdfFiles as $pdf) {
                $zip->addFile($pdf['path'], $pdf['name']);
            }

            $zip->close();

            // Clean up temp directory
            $this->deleteDirectory($tempDir);

            // Return ZIP file as download
            return $this->file(
                $zipPath,
                sprintf('evaluations_P%d_%s.zip', $period->getPeriodNumber(), date('Y-m-d_H-i-s')),
                ResponseHeaderBag::DISPOSITION_ATTACHMENT
            );

        } catch (\Exception $e) {
            // Clean up on error
            $this->deleteDirectory($tempDir);
            if (file_exists($zipPath ?? '')) {
                unlink($zipPath);
            }
            $this->addFlash('error', 'Une erreur s\'est produite lors de la génération de l\'archive : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get student evaluations by period
     */
    private function getStudentEvaluationData(SchoolYear $activeYear, User $student): array
    {
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

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
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


            $tutor = null;

            // Loop through the student's contracts to find an active tutor
            foreach ($student->getStudentContracts() as $tutorLink) {
                $start = $tutorLink->getDateDebutContract();
                $end = $tutorLink->getDateFinContract();

                if (($start === null || $start <= new \DateTime()) &&
                    ($end === null || $end >= new \DateTime())) {
                    $tutor = $tutorLink->getTutor();
                    break;
                }
            }

            // Decide which user to assign and the badge
            if (!$eval['tutor_validated'] && $tutor) {
                $user = $tutor;
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