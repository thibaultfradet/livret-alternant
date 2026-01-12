<?php

namespace App\Controller;

use App\Form\FormationCenterFilesType;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FormationCenterController extends AbstractController
{
    #[Route('/formation-center/files', name: 'app_formation_center_file', methods: ['GET', 'POST'])]
    public function uploadFile(
        Request $request,
        SchoolYearRepository $schoolYearRepo
    ): Response {
        $activeSchoolYear = $schoolYearRepo->findActive();

        // Define storage path for uploaded files
        $storagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/general/';

        // Create the form
        $form = $this->createForm(FormationCenterFilesType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('formation_center_file')->getData();

            if ($uploadedFile) {
                // Define allowed image MIME types
                $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!in_array($uploadedFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier doit être une image (JPEG, JPG, PNG, GIF, WebP).');
                } else {
                    // Generate new file name (formation-center-ID.extension)
                    $newFilename = sprintf('formation-center-%d.%s', $activeSchoolYear->getId(), "png");

                    // Move the file to the target directory
                    $uploadedFile->move($storagePath, $newFilename);

                    $this->addFlash('success', 'Fichier du centre de formation mis à jour avec succès.');

                    return $this->redirectToRoute('app_formation_center_file', ['id' => $activeSchoolYear->getId()]);
                }
            } else {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }
        }

        // Prepare file path for display (only if exists)
        $filePath = sprintf('/uploads/general/formation-center-%d.png', $activeSchoolYear->getId());
        $fileExists = file_exists($this->getParameter('kernel.project_dir') . '/public' . $filePath);

        return $this->render('formation_center/file.html.twig', [
            'form' => $form->createView(),
            'filePath' => $fileExists ? $filePath : null,
            'id' => $activeSchoolYear->getId(),
            'activeYear' => $activeSchoolYear,
        ]);
    }
}