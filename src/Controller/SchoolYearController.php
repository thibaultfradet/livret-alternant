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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $schoolYear = new SchoolYear();
        $schoolYear->setEstablishment($establishment);

        $form = $this->createForm(SchoolYearType::class, $schoolYear);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If this year is set as active, deactivate all other years for this establishment
            if ($schoolYear->isActive()) {
                $otherActiveYears = $entityManager->getRepository(SchoolYear::class)
                    ->findBy(['establishment' => $establishment, 'active' => true]);

                foreach ($otherActiveYears as $otherYear) {
                    $otherYear->setActive(false);
                    $entityManager->persist($otherYear);
                }
            }

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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check if school year belongs to user's establishment
        if ($schoolYear->getEstablishment() !== $user->getEstablishment()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les années scolaires de votre établissement.');
            return $this->redirectToRoute('training_parameters_schoolYear');
        }

        $form = $this->createForm(SchoolYearType::class, $schoolYear);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If this year is set as active, deactivate all other years for this establishment
            if ($schoolYear->isActive()) {
                $otherActiveYears = $entityManager->getRepository(SchoolYear::class)
                    ->findBy(['establishment' => $user->getEstablishment(), 'active' => true]);

                foreach ($otherActiveYears as $otherYear) {
                    // Don't deactivate the current year if it's already active
                    if ($otherYear->getId() !== $schoolYear->getId()) {
                        $otherYear->setActive(false);
                        $entityManager->persist($otherYear);
                    }
                }
            }

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
            $this->addFlash('error', 'Vous ne pouvez modifier que les années scolaires de votre établissement.');
            return $this->redirectToRoute('training_parameters_schoolYear');
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
            /** @var UploadedFile|null $coverFile1 */
            $coverFile1 = $form->get('cover_file_1')->getData();
            /** @var UploadedFile|null $coverFile2 */
            $coverFile2 = $form->get('cover_file_2')->getData();

            // Define allowed image MIME types
            $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            $filesUploaded = 0;

            // Handle first cover image upload
            if ($coverFile1) {
                if (!in_array($coverFile1->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Page de garde 1 doit être une image (JPEG, PNG, GIF, WebP).');
                } else {
                    $newFilename1 = sprintf('cover-image-1-%d.%s',
                        $schoolYear->getId(),
                        "png"
                    );
                    $coverFile1->move($storagePath, $newFilename1);
                    $filesUploaded++;
                }
            }

            // Handle second cover image upload
            if ($coverFile2) {
                if (!in_array($coverFile2->getMimeType(), $allowedImageTypes)) {
                    $this->addFlash('error', 'Le fichier Page de garde 2 doit être une image (JPEG, PNG, GIF, WebP).');
                } else {
                    $newFilename2 = sprintf('cover-image-2-%d.%s',
                        $schoolYear->getId(),
                        "png"
                    );
                    $coverFile2->move($storagePath, $newFilename2);
                    $filesUploaded++;
                }
            }

            if ($filesUploaded > 0) {
                $this->addFlash('success', sprintf('%d page(s) de garde mise(s) à jour avec succès.', $filesUploaded));
            } else {
                $this->addFlash('warning', 'Aucun fichier sélectionné.');
            }

            return $this->redirectToRoute('app_school_year_cover', ['id' => $schoolYear->getId()]);
        }

        // Prepare file paths for display
        $coverPath1 = sprintf('/uploads/covers/cover-image-1-%d.png',
            $schoolYear->getId()
        );
        $coverPath2 = sprintf('/uploads/covers/cover-image-2-%d.png',
            $schoolYear->getId()
        );

        return $this->render('school_year/cover.html.twig', [
            'schoolYear' => $schoolYear,
            'form' => $form->createView(),
            'coverFile1' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $coverPath1) ? $coverPath1 : null,
            'coverFile2' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $coverPath2) ? $coverPath2 : null,
        ]);
    }
}
