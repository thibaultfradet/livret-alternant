<?php

namespace App\Controller;

use App\Entity\Diploma;
use App\Form\DiplomaType;
use App\Repository\DiplomaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/diploma')]
final class DiplomaController extends AbstractController
{
    #[Route(name: 'app_diploma_index', methods: ['GET'])]
    public function index(DiplomaRepository $diplomaRepository): Response
    {
        return $this->render('diploma/index.html.twig', [
            'diplomas' => $diplomaRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_diploma_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $diploma = new Diploma();
        $form = $this->createForm(DiplomaType::class, $diploma);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($diploma);
            $entityManager->flush();

            return $this->redirectToRoute('app_diploma_index', [], Response::HTTP_SEE_OTHER);
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
        $form = $this->createForm(DiplomaType::class, $diploma);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_diploma_index', [], Response::HTTP_SEE_OTHER);
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

        return $this->redirectToRoute('app_diploma_index', [], Response::HTTP_SEE_OTHER);
    }
}
