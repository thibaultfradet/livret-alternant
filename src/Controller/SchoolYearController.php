<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Form\SchoolYearType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/school-year')]
final class SchoolYearController extends AbstractController
{

    #[Route('/new', name: 'app_school_year_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SchoolYearRepository $schoolYearRepository): Response
    {
        $schoolYear = new SchoolYear();
        $form = $this->createForm(SchoolYearType::class, $schoolYear);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // If the new SchoolYear is active, deactivate all others
            if ($schoolYear->isActive()) {
                $otherYears = $schoolYearRepository->findAll();
                foreach ($otherYears as $otherYear) {
                    $otherYear->setIsActive(false);
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
    public function edit(Request $request, SchoolYear $schoolYear, EntityManagerInterface $entityManager, SchoolYearRepository $schoolYearRepository): Response
    {
        $form = $this->createForm(SchoolYearType::class, $schoolYear);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // If the edited SchoolYear is active, deactivate all others
            if ($schoolYear->getIsActive()) {
                $otherYears = $schoolYearRepository->findAll();
                foreach ($otherYears as $otherYear) {
                    if ($otherYear->getId() !== $schoolYear->getId()) { // skip the current one
                        $otherYear->setIsActive(false);
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
}
