<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/user')]
#[IsGranted('ROLE_TTM')]
final class UserController extends AbstractController
{
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roles = ['ROLE_USER']; // role par défaut

            // Élève
            if ($form->get('isAlternance')->getData() || $request->request->has('student-tab')) {
                $roles[] = 'ROLE_STUDENT';
            }

            // Professeur référent
            if ($form->get('isProfPrincipal')->getData()) {
                $roles[] = 'ROLE_PT';
            }

            // Membre équipe pédagogique
            if ($form->get('isTeamMember')->getData()) {
                $roles[] = 'ROLE_TTM';
            }

            $user->setRoles(array_unique($roles));
            $user->setPassword('');

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
        }
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);

        // Pre-check checkboxes based on existing roles
        $form->get('isProfPrincipal')->setData(in_array('ROLE_PT', $user->getRoles()));
        $form->get('isTeamMember')->setData(in_array('ROLE_TTM', $user->getRoles()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roles = [];

            if ($form->get('isProfPrincipal')->getData()) {
                $roles[] = 'ROLE_PT';
            }

            if ($form->get('isTeamMember')->getData()) {
                $roles[] = 'ROLE_TTM';
            }

            // Preserve default ROLE_USER if applicable
            $roles[] = 'ROLE_USER';

            $user->setRoles(array_unique($roles));

            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}/disable', name: 'app_user_disable', methods: ['POST'])]
    public function disable(User $user, EntityManagerInterface $entityManager): Response
    {
        // If user is already disabled, just redirect
        if ($user->getDisabledAt() !== null) {
            $this->addFlash('info', 'Cet utilisateur est déjà désactivé.');
            return $this->redirectToRoute('training_parameters_user');
        }

        // Set the disabled date to now
        $user->setDisabledAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'L’utilisateur a été supprimé avec succès.');

        return $this->redirectToRoute('training_parameters_user');
    }


    #[Route('/modes/list', name: 'app_user_list_modes', methods: ['GET'])]
    public function listModes(UserRepository $userRepo): Response
    {
        // Get the currently authenticated user
        /** @var User $currentUser */
        $currentUser = $this->getUser();
    
        // Safety check in case no user is authenticated
        if (!$currentUser) {
            throw $this->createAccessDeniedException('User not authenticated.');
        }
    
        // Get the establishment of the connected user
        $establishment = $currentUser->getEstablishment();
    
        // Retrieve only students from the same establishment
        $users = $userRepo->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->andWhere('u.establishment = :establishment')
            ->setParameter('role', '%ROLE_STUDENT%')
            ->setParameter('establishment', $establishment)
            ->getQuery()
            ->getResult();
    
        return $this->render('user/list_modes.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/toggle-alternance', name: 'app_user_toggle_alternance', methods: ['POST'])]
    public function toggleAlternance(User $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $isAlternance = filter_var($request->request->get('isAlternance'), FILTER_VALIDATE_BOOLEAN);

        $user->setIsAlternance($isAlternance);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'isAlternance' => $user->isAlternance()
        ]);
    }
}