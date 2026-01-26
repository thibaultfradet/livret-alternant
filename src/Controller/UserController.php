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

            $isProfPrincipal = $form->get('isProfPrincipal')->getData();
            $isTeamMember = $form->get('isTeamMember')->getData();

            // Professeur référent
            if ($isProfPrincipal) {
                $roles[] = 'ROLE_PT';
            }

            // Membre équipe pédagogique
            if ($isTeamMember) {
                $roles[] = 'ROLE_TTM';
            }

            // Élève (si ni prof référent ni membre équipe pédagogique)
            if (!$isProfPrincipal && !$isTeamMember) {
                $roles[] = 'ROLE_STUDENT';
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
                            $tutor->setCompanyName($companyName);
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

        // Find active contract for students
        $activeContract = null;
        $isStudent = in_array('ROLE_STUDENT', $user->getRoles());

        if ($isStudent) {
            $now = new \DateTime();
            foreach ($user->getStudentContracts() as $contract) {
                $startDate = $contract->getDateDebutContract();
                $endDate = $contract->getDateFinContract();

                if ((!$startDate || $startDate <= $now) && (!$endDate || $endDate >= $now)) {
                    $activeContract = $contract;
                    break;
                }
            }
        }

        $form = $this->createForm(UserType::class, $user);

        // Pre-check checkboxes based on existing roles
        $form->get('isProfPrincipal')->setData(in_array('ROLE_PT', $user->getRoles()));
        $form->get('isTeamMember')->setData(in_array('ROLE_TTM', $user->getRoles()));
        $form->get('isAlternance')->setData($user->isAlternance());

        // Pre-fill tutor fields if active contract exists
        if ($activeContract && $activeContract->getTutor()) {
            $tutor = $activeContract->getTutor();
            $form->get('tutorFirstName')->setData($tutor->getFirstName());
            $form->get('tutorLastName')->setData($tutor->getLastName());
            $form->get('tutorEmail')->setData($tutor->getEmail());
            $form->get('tutorPhone')->setData($tutor->getPhone());
            $form->get('companyName')->setData($tutor->getCompanyName());
            $form->get('companyAddress')->setData($tutor->getCompanyAddress());
            $form->get('dateDebutContract')->setData($activeContract->getDateDebutContract());
            $form->get('dateFinContract')->setData($activeContract->getDateFinContract());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check again establishment change
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

            // Preserve ROLE_STUDENT if user is a student
            if ($isStudent) {
                $roles[] = 'ROLE_STUDENT';
            }

            // Preserve default ROLE_USER
            $roles[] = 'ROLE_USER';

            $user->setRoles(array_unique($roles));

            // Update tutor information if user is a student and has an active contract
            if ($isStudent && $activeContract && $activeContract->getTutor()) {
                $tutor = $activeContract->getTutor();

                $tutor->setFirstName($form->get('tutorFirstName')->getData());
                $tutor->setLastName($form->get('tutorLastName')->getData());
                $tutor->setEmail($form->get('tutorEmail')->getData());
                $tutor->setPhone($form->get('tutorPhone')->getData());
                $tutor->setCompanyName($form->get('companyName')->getData());
                $tutor->setCompanyAddress($form->get('companyAddress')->getData());

                $activeContract->setDateDebutContract($form->get('dateDebutContract')->getData());
                $activeContract->setDateFinContract($form->get('dateFinContract')->getData());
            }

            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'isStudent' => $isStudent,
            'activeContract' => $activeContract,
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