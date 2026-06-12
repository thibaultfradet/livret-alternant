<?php

namespace App\Controller;

use App\Entity\Diploma;
use App\Form\DiplomaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/diploma')]
#[IsGranted('ROLE_TTM')]
final class DiplomaController extends AbstractController
{
    use EstablishmentGuardTrait;

    #[Route('/new', name: 'app_diploma_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $diploma = new Diploma();
        $form = $this->createForm(DiplomaType::class, $diploma);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $diploma->setEstablishment($user->getEstablishment());
            $entityManager->persist($diploma);
            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_diploma', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('diploma/new.html.twig', [
            'diploma' => $diploma,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_diploma_show', methods: ['GET'])]
    public function show(Diploma $diploma): Response
    {
        return $this->render('diploma/show.html.twig', [
            'diploma' => $diploma,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_diploma_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Diploma $diploma, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->denyIfForeignEstablishment($diploma, 'training_parameters_diploma', 'Vous ne pouvez gérer que les diplômes de votre établissement.')) {
            return $redirect;
        }

        $form = $this->createForm(DiplomaType::class, $diploma);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_diploma', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('diploma/edit.html.twig', [
            'diploma' => $diploma,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_diploma_delete', methods: ['POST'])]
    public function delete(Request $request, Diploma $diploma, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$diploma->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($diploma);
            $entityManager->flush();
        }

        return $this->redirectToRoute('training_parameters_diploma', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/disable/{id}', name: 'app_diploma_disable', methods: ['POST'])]
    public function disable(Request $request, Diploma $diploma, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('disable'.$diploma->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('training_parameters_diploma');
        }

        if ($redirect = $this->denyIfForeignEstablishment($diploma, 'training_parameters_diploma', 'Vous ne pouvez gérer que les diplômes de votre établissement.')) {
            return $redirect;
        }

        if ($diploma->getDisabledAt() !== null) {
            $this->addFlash('warning', 'Ce diplôme est déjà désactivé.');
            return $this->redirectToRoute('training_parameters_diploma');
        }

        // Disable the diploma
        $diploma->setDisabledAt(new \DateTime());

        $em->persist($diploma);
        $em->flush();

        $this->addFlash('success', 'Le diplôme a été supprimé avec succès.');

        return $this->redirectToRoute('training_parameters_diploma');
    }
}
