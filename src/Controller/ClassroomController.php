<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClassroomController extends AbstractController
{
    #[Route('/classroom/{id}/files', name: 'app_classroom_files', methods: ['GET', 'POST'])]
    public function index(Request $request, int $id): Response
    {
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
                    $calendarFile->move($storagePath, sprintf('calendar-%d.pdf', $id));
                    $this->addFlash('success', 'Calendrier mis à jour.');
                }
            }

            // Process Schedule file
            if ($scheduleFile) {
                if (!in_array($scheduleFile->getMimeType(), ['application/pdf', 'application/x-pdf'])) {
                    $this->addFlash('error', 'Le fichier Emploi du temps doit être un PDF.');
                } else {
                    $scheduleFile->move($storagePath, sprintf('schedule-%d.pdf', $id));
                    $this->addFlash('success', 'Emploi du temps mis à jour.');
                }
            }

            if (!$calendarFile && !$scheduleFile) {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }

            return $this->redirectToRoute('app_classroom_files', ['id' => $id]);
        }

        // Check if files exist
        $calendarPath = sprintf('/uploads/classroom/calendar-%d.pdf', $id);
        $schedulePath = sprintf('/uploads/classroom/schedule-%d.pdf', $id);

        return $this->render('classroom/files.html.twig', [
            'id' => $id,
            'calendarFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $calendarPath) ? $calendarPath : null,
            'scheduleFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $schedulePath) ? $schedulePath : null,
        ]);
    }
}
