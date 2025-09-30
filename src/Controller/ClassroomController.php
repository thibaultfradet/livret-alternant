<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Classroom;
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
    #[Route('/classroom/{id}/files', name: 'app_classroom_files', methods: ['GET', 'POST'])]
    public function index(
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

        if ($request->isMethod('POST')) {
            $calendarFile = $request->files->get('calendar_file');
            $scheduleFile = $request->files->get('schedule_file');

            // Process Calendar file
            if ($calendarFile) {
                if (!in_array($calendarFile->getMimeType(), ['application/pdf', 'application/x-pdf'])) {
                    $this->addFlash('error', 'Le fichier Calendrier doit être un PDF.');
                } else {
                    // Save the file
                    $calendarFile->move($storagePath, sprintf('calendar-%d.pdf', $id));
                    $this->addFlash('success', 'Calendrier mis à jour.');

                    // Call mailing function
                    $this->sendUpdateEmails($id, $mailer, $em, 'Calendrier');
                }
            }

            // Process Schedule file
            if ($scheduleFile) {
                if (!in_array($scheduleFile->getMimeType(), ['application/pdf', 'application/x-pdf'])) {
                    $this->addFlash('error', 'Le fichier Emploi du temps doit être un PDF.');
                } else {
                    // Save the file
                    $scheduleFile->move($storagePath, sprintf('schedule-%d.pdf', $id));
                    $this->addFlash('success', 'Emploi du temps mis à jour.');

                    // Call mailing function
                    $this->sendUpdateEmails($id, $mailer, $em, 'Emploi du temps');
                }
            }

            if (!$calendarFile && !$scheduleFile) {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }

            return $this->redirectToRoute('app_classroom_files', ['id' => $id]);
        }

        // Check if file exists
        $calendarPath = sprintf('/uploads/classroom/calendar-%d.pdf', $id);
        $schedulePath = sprintf('/uploads/classroom/schedule-%d.pdf', $id);

        return $this->render('classroom/files.html.twig', [
            'id' => $id,
            'calendarFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $calendarPath) ? $calendarPath : null,
            'scheduleFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $schedulePath) ? $schedulePath : null,
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
                    // English comments: We personalize the message with the student's last name
                    "Bonjour %s,\n\nLe %s de votre classe vient d'être mis à jour.\n\nVeuillez le consulter dans votre espace.\n\nCordialement,\nL'équipe pédagogique",
                    $student->getLastName(),
                    strtolower($fileType)
                ));

            // Send email
            $mailer->send($email);
        }
    }
}
