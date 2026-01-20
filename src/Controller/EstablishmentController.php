<?php

namespace App\Controller;

use App\Entity\Establishment;
use App\Form\EstablishmentGeneralType;
use App\Form\EstablishmentCenterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EstablishmentController extends AbstractController
{
    // Edit general information (name + general terms)
    #[Route('/establishment/general', name: 'app_establishment_general', methods: ['GET', 'POST'])]
    public function editGeneral(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user || !$user->getEstablishment()) {
            throw $this->createAccessDeniedException('No establishment associated with the current user.');
        }

        $establishment = $user->getEstablishment();
        $form = $this->createForm(EstablishmentGeneralType::class, $establishment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Informations générales mises à jour avec succès.');
            return $this->redirectToRoute('app_establishment_general');
        }

        return $this->render('establishment/edit_general.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/establishment/center', name: 'app_establishment_center_edit', methods: ['GET', 'POST'])]
    public function editCenter(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_TTM', $user->getRoles())) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier le centre de formation.');
        }

        $establishment = $user->getEstablishment();
        $form = $this->createForm(EstablishmentCenterType::class, $establishment->getFormationCenter());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $establishment->setFormationCenter($form->getData());
            $em->persist($establishment);
            $em->flush();

            $this->addFlash('success', 'Centre de formation mis à jour avec succès.');
            return $this->redirectToRoute('app_establishment_center_edit');
        }

        return $this->render('establishment/edit_center.html.twig', [
            'form' => $form->createView(),
            'establishment' => $establishment,
        ]);
    }

    // View establishment details (read-only)
    #[Route('/establishment/view', name: 'app_establishment_view', methods: ['GET'])]
    public function view(): Response
    {
        $user = $this->getUser();

        if (!$user || !$user->getEstablishment()) {
            throw $this->createAccessDeniedException('No establishment associated with the current user.');
        }

        $establishment = $user->getEstablishment();

        return $this->render('establishment/view.html.twig', [
            'establishment' => $establishment,
        ]);
    }
}