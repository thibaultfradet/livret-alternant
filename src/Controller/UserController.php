<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TutorStudent;
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
        $currentUser = $this->getUser();
        $establishment = $currentUser->getEstablishment();

        if (!$establishment) {
            $this->addFlash('error', 'Vous n\'êtes pas assigné à un établissement.');
            return $this->redirectToRoute('training_parameters_user');
        }

        $user = new User();
        // pre-set establishment
        $user->setEstablishment($establishment);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($user->getEstablishment() !== $establishment) {
                $this->addFlash('error', 'Vous ne pouvez créer des utilisateurs que pour votre établissement.');
                return $this->redirectToRoute('training_parameters_user');
            }

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

            // Handle tutor creation if user is a student
            if (in_array('ROLE_STUDENT', $roles)) {
                $tutorEmail = $form->get('tutorEmail')->getData();
                $tutorFirstName = $form->get('tutorFirstName')->getData();
                $tutorLastName = $form->get('tutorLastName')->getData();
                $tutorPhone = $form->get('tutorPhone')->getData();
                $companyName = $form->get('companyName')->getData();
                $companyAddress = $form->get('companyAddress')->getData();
                $dateDebutContract = $form->get('dateDebutContract')->getData();
                $dateFinContract = $form->get('dateFinContract')->getData();

                // Only create tutor if email is provided
                if ($tutorEmail) {
                    // Check if tutor already exists
                    $existingTutor = $entityManager->getRepository(User::class)->findOneBy(['email' => $tutorEmail]);

                    if (!$existingTutor) {
                        // Create new tutor
                        $tutor = new User();
                        $tutor->setFirstName($tutorFirstName);
                        $tutor->setLastName($tutorLastName);
                        $tutor->setEmail($tutorEmail);
                        $tutor->setRoles(['ROLE_TUTOR']);
                        $tutor->setPassword('');

                        if ($companyName) {
                            $tutor->setCompanyname($companyName);
                        }
                        if ($companyAddress) {
                            $tutor->setCompanyAddress($companyAddress);
                        }
                        if ($tutorPhone) {
                            $tutor->setPhone($tutorPhone);
                        }

                        $entityManager->persist($tutor);
                        $entityManager->flush();
                    } else {
                        // Reactivate tutor if previously disabled
                        if ($existingTutor->getDisabledAt() !== null) {
                            $existingTutor->setDisabledAt(null);
                            $entityManager->persist($existingTutor);
                            $entityManager->flush();
                        }

                        $tutor = $existingTutor;
                    }

                    // Create TutorStudent relationship
                    $tutorStudent = new TutorStudent();
                    $tutorStudent->setStudent($user);
                    $tutorStudent->setTutor($tutor);

                    // Set contract dates
                    if ($dateDebutContract) {
                        $tutorStudent->setDateDebutContract($dateDebutContract);
                    } else {
                        $tutorStudent->setDateDebutContract(new \DateTime());
                    }

                    if ($dateFinContract) {
                        $tutorStudent->setDateFinContract($dateFinContract);
                    } else {
                        $tutorStudent->setDateFinContract((new \DateTime())->modify('+6 months'));
                    }

                    $entityManager->persist($tutorStudent);
                    $entityManager->flush();
                }
            }

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
        $activeContract = null;

        // If user is a student, find the active contract
        if (in_array('ROLE_STUDENT', $user->getRoles())) {
            $now = new \DateTime();
            foreach ($user->getStudentContracts() as $contract) {
                $startDate = $contract->getDateDebutContract();
                $endDate = $contract->getDateFinContract();

                // Check if contract is currently active
                if ((!$startDate || $startDate <= $now) && (!$endDate || $endDate >= $now)) {
                    $activeContract = $contract;
                    break;
                }
            }
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'activeContract' => $activeContract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {

        $currentUser = $this->getUser();

        // check same establishment
        if ($user->getEstablishment() !== $currentUser->getEstablishment()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les utilisateurs de votre établissement.');
            return $this->redirectToRoute('training_parameters_user');
        }
        
        $form = $this->createForm(UserType::class, $user);

        // Pre-check checkboxes based on existing roles
        $form->get('isProfPrincipal')->setData(in_array('ROLE_PT', $user->getRoles()));
        $form->get('isTeamMember')->setData(in_array('ROLE_TTM', $user->getRoles()));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //check again establishment change
            if ($user->getEstablishment() !== $currentUser->getEstablishment()) {
                $this->addFlash('error', 'Vous ne pouvez pas changer l\'établissement.');
                return $this->redirectToRoute('training_parameters_user');
            }

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
            $this->addFlash('error', 'User not authenticated.');
            return $this->redirectToRoute('app_home');
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