<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Form\SchoolYearCoverType;
use App\Form\SchoolYearType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/school-year')]
#[IsGranted('ROLE_TTM')]
final class SchoolYearController extends AbstractController
{

    #[Route('/new', name: 'app_school_year_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $schoolYear = new SchoolYear();
        $form = $this->createForm(SchoolYearType::class, $schoolYear);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($schoolYear);
            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_schoolYear', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('school_year/new.html.twig', [
            'school_year' => $schoolYear,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_school_year_show', methods: ['GET'])]
    public function show(SchoolYear $schoolYear): Response
    {
        return $this->render('school_year/show.html.twig', [
            'school_year' => $schoolYear,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_school_year_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SchoolYear $schoolYear, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SchoolYearType::class, $schoolYear);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_schoolYear', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('school_year/edit.html.twig', [
            'school_year' => $schoolYear,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_school_year_delete', methods: ['POST'])]
    public function delete(Request $request, SchoolYear $schoolYear, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$schoolYear->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($schoolYear);
            $entityManager->flush();
        }

        return $this->redirectToRoute('training_parameters_schoolYear', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/disable/{id}', name: 'app_school_year_disable')]
    public function disable(SchoolYear $schoolYear, EntityManagerInterface $em)
    {
        if (!$schoolYear) {
            $this->addFlash('error', "L'année scolaire est introuvable.");
            return $this->redirectToRoute('training_parameters_schoolYear');
        }

        if ($schoolYear->getDisabledAt() !== null) {
            $this->addFlash('warning', 'Cette année scolaire est déjà désactivé.');
            return $this->redirectToRoute('training_parameters_schoolYear');
        }

        // Disable the school year
        $schoolYear->setDisabledAt(new \DateTime());

        $em->persist($schoolYear);
        $em->flush();

        $this->addFlash('success', "L'année scolaire a été supprimé avec succès.");

        return $this->redirectToRoute('training_parameters_schoolYear');
    }

    #[Route('/{id}/cover', name: 'app_school_year_cover', methods: ['GET', 'POST'])]
    public function uploadCover(Request $request, SchoolYear $schoolYear, EntityManagerInterface $em): Response
    {
        // Check if school year belongs to user's establishment
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($schoolYear->getEstablishment() !== $user->getEstablishment()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que les années scolaires de votre établissement.');
        }

        $storagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/covers/';

        // Create directory if it doesn't exist
        // if (!is_dir($storagePath)) {
        //     mkdir($storagePath, 0777, true);
        // }

        // Create and handle the Symfony form
        $form = $this->createForm(SchoolYearCoverType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $coverFile */
            $coverFile = $form->get('cover_file')->getData();

            // Define allowed image MIME types
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            // Handle cover image upload
            if ($coverFile) {
                if (!in_array($coverFile->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier doit être une image (JPEG, PNG, GIF, WebP).');
                } else {
                    // Generate filename: cover-page-{year_id}.png
                    $newFilename = sprintf('cover-page-%d.%s',
                        $schoolYear->getId(),
                        "png"
                    );

                    $coverFile->move($storagePath, $newFilename);
                    $this->addFlash('success', 'Page de garde mise à jour avec succès.');
                }
            } else {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }

            return $this->redirectToRoute('app_school_year_cover', ['id' => $schoolYear->getId()]);
        }

        // Prepare file path for display
        $coverPath = sprintf('/uploads/covers/cover-page-%d.png',
            $schoolYear->getId()
        );

        return $this->render('school_year/cover.html.twig', [
            'schoolYear' => $schoolYear,
            'form' => $form->createView(),
            'coverFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $coverPath) ? $coverPath : null,
        ]);
    }
}
