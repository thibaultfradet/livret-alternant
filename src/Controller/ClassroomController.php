<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Classroom;
use App\Form\ClassroomFilesType;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Address;

class ClassroomController extends AbstractController
{
    #[Route('/classroom', name: 'app_classroom_index')]
    public function index(ClassroomRepository $classroomRepository, SchoolYearRepository $schoolYearRepository): Response
    {
        // Get the active school year (assuming you have a boolean "isActive" field)
        $activeYear = $schoolYearRepository->findActive();

        // If no active year found, show an error or empty list
        if (!$activeYear) {
            $this->addFlash('warning', 'Aucune année active trouvée.');
            return $this->render('user_classroom/index.html.twig', [
                'classrooms' => [],
                'activeYear' => null,
            ]);
        }

        // Get all classrooms for that school year
        $classrooms = $classroomRepository->findBy(['schoolYear' => $activeYear]);

        return $this->render('classroom/index.html.twig', [
            'classrooms' => $classrooms,
            'activeYear' => $activeYear,
        ]);
    }


    #[Route('/classroom/{id}/files', name: 'app_classroom_files', methods: ['GET', 'POST'])]
    public function files(
        Request $request, 
        int $id, 
        MailerInterface $mailer, 
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TTM');

        $storagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/classroom/';
        $fs = new Filesystem();
        if (!$fs->exists($storagePath)) {
            $fs->mkdir($storagePath, 0777);
        }

        // create and handle the Symfony form
        $form = $this->createForm(ClassroomFilesType::class);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $calendarFile */
            $calendarFile = $form->get('calendar_file')->getData();
            /** @var UploadedFile|null $scheduleFile */
            $scheduleFile = $form->get('schedule_file')->getData();
            /** @var UploadedFile|null $scheduleFile */
            $teacherListFile = $form->get('teacher_list')->getData();

            //  Define allowed image MIME types
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            // Handle calendar image upload
            if ($calendarFile) {
                if (!in_array($calendarFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Calendrier doit être une image (JPG, PNG, GIF, WebP).');
                } else {
                    $newFilename = sprintf('calendar-%d.%s', $id, $calendarFile->guessExtension());
                    $calendarFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Calendrier mis à jour.');
                    $this->sendUpdateEmails($id, $mailer, $em, 'Calendrier');
                }
            }

            // Handle schedule image upload
            if ($scheduleFile) {
                if (!in_array($scheduleFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Emploi du temps doit être une image (JPG, PNG, GIF, WebP).');
                } else {
                    $newFilename = sprintf('schedule-%d.%s', $id, $scheduleFile->guessExtension());
                    $scheduleFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Emploi du temps mis à jour.');
                    $this->sendUpdateEmails($id, $mailer, $em, 'Emploi du temps');
                }
            }


            if ($teacherListFile) {
                if (!in_array($teacherListFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Liste des professeurs doit être une image (JPG, PNG, GIF, WebP).');
                } else {
                    $newFilename = sprintf('teacher-list-%d.%s', $id, $teacherListFile->guessExtension());
                    $teacherListFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Liste des professeurs mis à jour.');
                }
            }

            if (!$calendarFile && !$scheduleFile && !$teacherListFile) {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }

            return $this->redirectToRoute('app_classroom_files', ['id' => $id]);
        }

        // Prepare file paths for display
        $calendarPath = sprintf('/uploads/classroom/calendar-%d.jpg', $id);
        $schedulePath = sprintf('/uploads/classroom/schedule-%d.jpg', $id);
        $teacherListPath = sprintf('/uploads/classroom/teacher-list-%d.jpg', $id);

        return $this->render('classroom/files.html.twig', [
            'id' => $id,
            'form' => $form->createView(),
            'calendarFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $calendarPath) ? $calendarPath : null,
            'scheduleFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $schedulePath) ? $schedulePath : null,
            'teacherListFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $teacherListPath )? $teacherListPath : null,
        ]);
    }



    /**
     * Send email notification to all students when a file (calendar or schedule) is updated
     */
    private function sendUpdateEmails(int $classroomId, MailerInterface $mailer, EntityManagerInterface $em, string $fileType): void
    {
        /** @var Classroom $classroom */
        $classroom = $em->getRepository(Classroom::class)->find($classroomId);

        if (!$classroom) {
            return;
        }

        $students = $em->getRepository(User::class)->findBy(['classroom' => $classroom]);

        foreach ($students as $student) {
            // Create email message
            $email = (new Email())
                ->from(new Address('test@example.com', "Livret de l'alternant - Mise à jour du $fileType"))
                ->to($student->getEmail())
                ->subject("Mise à jour du $fileType de votre classe")
                ->text(sprintf(
                    // We personalize the message with the student's last name
                    "Bonjour %s,\n\nLe %s de votre classe vient d'être mis à jour.\n\nVeuillez le consulter dans votre espace.\n\nCordialement,\nL'équipe pédagogique",
                    $student->getLastName(),
                    strtolower($fileType)
                ));

            // Send email
            $mailer->send($email);
        }
    }
}
