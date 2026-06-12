<?php

namespace App\Controller;

use App\Entity\Period;
use App\Form\PeriodType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/period')]
#[IsGranted('ROLE_TTM')]
final class PeriodController extends AbstractController
{

    #[Route('/new', name: 'app_period_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $period = new Period();
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $form = $this->createForm(PeriodType::class, $period, [
            'currentUser' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($period);
            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_period', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('period/new.html.twig', [
            'period' => $period,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_period_show', methods: ['GET'])]
    public function show(Period $period): Response
    {
        return $this->render('period/show.html.twig', [
            'period' => $period,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_period_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Period $period, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $form = $this->createForm(PeriodType::class, $period, [
            'currentUser' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_period', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('period/edit.html.twig', [
            'period' => $period,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_period_delete', methods: ['POST'])]
    public function delete(Request $request, Period $period, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$period->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($period);
            $entityManager->flush();
        }

        return $this->redirectToRoute('training_parameters_period', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/disable/{id}', name: 'app_period_disable', methods: ['POST'])]
    public function disable(Request $request, Period $period, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('disable'.$period->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('training_parameters_period');
        }

        if ($period->getDisabledAt() !== null) {
            $this->addFlash('warning', 'Cette période est déjà désactivée.');
            return $this->redirectToRoute('training_parameters_period');
        }

        // Disable the period
        $period->setDisabledAt(new \DateTime());

        $em->persist($period);
        $em->flush();

        $this->addFlash('success', 'La période a été supprimé avec succès.');

        return $this->redirectToRoute('training_parameters_period');
    }
}
