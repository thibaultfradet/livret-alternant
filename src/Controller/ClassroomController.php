<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Classroom;
use App\Form\ClassroomFilesType;
use App\Form\ClassroomNewType;
use App\Form\ClassroomPrincipalTeacherType;
use App\Form\ClassroomTermsType;
use App\Form\ClassroomTrainingContactType;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Address;

class ClassroomController extends AbstractController
{


    #[Route('/classroom/new', name: 'app_classroom_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        SchoolYearRepository $schoolYearRepository,
        EntityManagerInterface $em
    ): Response {
        $classroom = new Classroom();

        // get current user establishment
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user && $user->getEstablishment()) {
            $establishment = $user->getEstablishment();

            // set terms of establishment && formation center
            $classroom->setFormationCenter($establishment->getFormationCenter());
            $classroom->setTermsConditionsPro($establishment->getTermsConditionsPro());
            $classroom->setTermsConditionsAlternance($establishment->getTermsConditionsAlternance());
        }

        //Get current school year
        $activeYear = $schoolYearRepository->findActiveByEstablishment();

        $form = $this->createForm(ClassroomNewType::class, $classroom, [
            'activeSchoolYear' => $activeYear,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($classroom);
            $em->flush();

            $this->addFlash('success', 'Classe créée avec succès.');
            return $this->redirectToRoute('training_parameters_classroom');
        }

        return $this->render('classroom/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    // change classroom principal teacher
    #[Route('/classroom/{id}/principal-teacher', name: 'app_classroom_tp', methods: ['GET', 'POST'])]
    public function assignPrincipalTeacher(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var Classroom $classroom */
        $classroom = $em->getRepository(Classroom::class)->find($id);

        if (!$classroom) {
            $this->addFlash('error', 'Classe introuvable.');
            return $this->redirectToRoute('training_parameters_classroom');
        }

        // Create form with a select of users who are not ROLE_STUDENT
      $form = $this->createForm(ClassroomPrincipalTeacherType::class, $classroom);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($classroom);
        $em->flush();

        $this->addFlash('success', 'Professeur principal mis à jour.');
        return $this->redirectToRoute('training_parameters_classroom');
    }
        
        return $this->render('classroom/principal_teacher.html.twig', [
            'classroom' => $classroom,
            'form' => $form->createView(),
        ]);
    }




    // Change classroom files
    #[Route('/classroom/{id}/files', name: 'app_classroom_files', methods: ['GET', 'POST'])]
    public function files(
        Request $request, 
        Classroom $classroom, 
        MailerInterface $mailer, 
        EntityManagerInterface $em
    ): Response {

        $storagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/classroom/';
        // $fs = new Filesystem();
        // if (!$fs->exists($storagePath)) {
        //     $fs->mkdir($storagePath, 0777);
        // }

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
                    $this->addFlash('error', 'Le fichier Calendrier doit être une image (JPEG, JPG, PNG, GIF, WebP).');
                } else {
                    $newFilename = sprintf('calendar-%d.%s', $classroom->getId(), "png");
                    $calendarFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Calendrier mis à jour.');
                    $this->sendUpdateEmails($classroom->getId(), $mailer, $em, 'Calendrier');
                }
            }

            // Handle schedule image upload
            if ($scheduleFile) {
                if (!in_array($scheduleFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Emploi du temps doit être une image (JPEG , JPG, PNG, GIF, WebP).');
                } else {
                    $newFilename = sprintf('schedule-%d.%s', $classroom->getId(), "png");
                    $scheduleFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Emploi du temps mis à jour.');
                    $this->sendUpdateEmails($classroom->getId(), $mailer, $em, 'Emploi du temps');
                }
            }


            if ($teacherListFile) {
                if (!in_array($teacherListFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Liste des professeurs doit être une image (JPEG, JPG, PNG, GIF, WebP).');
                } else {
                    $newFilename = sprintf('teacher-list-%d.%s', $classroom->getId(), "png");
                    $teacherListFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Liste des professeurs mis à jour.');
                }
            }

            if (!$calendarFile && !$scheduleFile && !$teacherListFile) {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }

            return $this->redirectToRoute('app_classroom_files', ['id' => $classroom->getId()]);
        }

        // Prepare file paths for display
        $calendarPath = sprintf('/uploads/classroom/calendar-%d.png', $classroom->getId());
        $schedulePath = sprintf('/uploads/classroom/schedule-%d.png', $classroom->getId());
        $teacherListPath = sprintf('/uploads/classroom/teacher-list-%d.png', $classroom->getId());

        return $this->render('classroom/files.html.twig', [
            'classroom' => $classroom,
            'form' => $form->createView(),
            'calendarFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $calendarPath) ? $calendarPath : null,
            'scheduleFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $schedulePath) ? $schedulePath : null,
            'teacherListFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $teacherListPath )? $teacherListPath : null,
        ]);
    }


    
    #[Route('/classroom/{id}/training-contact', name: 'app_classroom_training_contact')]
    public function editTrainingContact(
        Classroom $classroom,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Create the form bound to the Classroom entity
        $form = $this->createForm(ClassroomTrainingContactType::class, $classroom);

        // Handle request data
        $form->handleRequest($request);

        // If form is submitted and valid, persist changes
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($classroom);
            $entityManager->flush();

            $this->addFlash('success', 'Référent de formation mis à jour avec succès.');

            // Redirect after successful update
            return $this->redirectToRoute('training_parameters_classroom');
        }

        // Render the form
        return $this->render('classroom/edit_training_contact.html.twig', [
            'classroom' => $classroom,
            'form' => $form->createView(),
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


    #[Route('/classroom/{id}/terms', name: 'app_classroom_terms', methods: ['GET', 'POST'])]
    public function editTerms(
        Classroom $classroom,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Create the form for editing classroom terms
        $form = $this->createForm(ClassroomTermsType::class, $classroom);

        // Handle form submission
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persist changes to the database
            $entityManager->persist($classroom);
            $entityManager->flush();

            $this->addFlash('success', 'Classroom terms updated successfully.');

            // Redirect back to classroom list or details page
            return $this->redirectToRoute('training_parameters_classroom');
        }

        // Render the form template
        return $this->render('classroom/edit_terms.html.twig', [
            'classroom' => $classroom,
            'form' => $form->createView(),
        ]);
    }
}
