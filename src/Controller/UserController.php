<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TutorStudent;
use App\Form\StaffType;
use App\Form\TutorType;
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

        $form = $this->createForm(UserType::class, $user, ['establishment' => $establishment]);
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

            // Handle tutor assignment if user is a student
            if (in_array('ROLE_STUDENT', $roles)) {
                $dateDebutContract = $form->get('dateDebutContract')->getData();
                $dateFinContract = $form->get('dateFinContract')->getData();

                $tutor = null;

                // Priority: existing tutor selected from dropdown
                $selectedTutor = $form->get('existingTutor')->getData();
                if ($selectedTutor) {
                    $tutor = $selectedTutor;
                    $this->enableTutor($tutor);
                    $entityManager->persist($tutor);
                } else {
                    // Fallback: create or find tutor by email
                    $tutorEmail = $form->get('tutorEmail')->getData();
                    if ($tutorEmail) {
                        $tutorFirstName = $form->get('tutorFirstName')->getData();
                        $tutorLastName = $form->get('tutorLastName')->getData();
                        $tutorPhone = $form->get('tutorPhone')->getData();
                        $companyName = $form->get('companyName')->getData();
                        $companyAddress = $form->get('companyAddress')->getData();

                        $tutor = $entityManager->getRepository(User::class)->findOneBy(['email' => $tutorEmail]);

                        if (!$tutor) {
                            $tutor = new User();
                            $tutor->setFirstName($tutorFirstName);
                            $tutor->setLastName($tutorLastName);
                            $tutor->setEmail($tutorEmail);
                            $tutor->setRoles(['ROLE_TUTOR', 'ROLE_USER']);
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
                        } else {
                            $this->enableTutor($tutor);
                        }

                        $entityManager->persist($tutor);
                    }
                }

                if ($tutor) {
                    $tutorStudent = new TutorStudent();
                    $tutorStudent->setStudent($user);
                    $tutorStudent->setTutor($tutor);
                    $tutorStudent->setDateDebutContract($dateDebutContract ?? new \DateTime());
                    $tutorStudent->setDateFinContract($dateFinContract ?? (new \DateTime())->modify('+6 months'));

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
        $activeContract = $this->findActiveContract($user);

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'activeContract' => $activeContract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        // check same establishment (tutors have no establishment, skip check for them)
        $isTutor = in_array('ROLE_TUTOR', $user->getRoles(), true);
        if (!$isTutor && $user->getEstablishment() !== $currentUser->getEstablishment()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les utilisateurs de votre établissement.');
            return $this->redirectToRoute('training_parameters_user');
        }

        // Tutor edition: dedicated form, role preserved, type cannot change here
        if ($isTutor) {
            $tutorForm = $this->createForm(TutorType::class, $user);
            $tutorForm->handleRequest($request);

            if ($tutorForm->isSubmitted() && $tutorForm->isValid()) {
                $roles = ['ROLE_TUTOR', 'ROLE_USER'];
                if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    $roles[] = 'ROLE_ADMIN';
                }
                $user->setRoles(array_unique($roles));
                $entityManager->flush();

                return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('user/edit.html.twig', [
                'user' => $user,
                'form' => $tutorForm,
                'userType' => 'tutor',
                'isStudent' => false,
                'activeContract' => null,
            ]);
        }

        // Staff edition (Professeur référent / Membre équipe pédagogique): dedicated form.
        $isStudent = in_array('ROLE_STUDENT', $user->getRoles(), true);
        if (!$isStudent) {
            $staffForm = $this->createForm(StaffType::class, $user);
            $staffForm->get('isProfPrincipal')->setData(in_array('ROLE_PT', $user->getRoles(), true));
            $staffForm->get('isTeamMember')->setData(in_array('ROLE_TTM', $user->getRoles(), true));
            $staffForm->handleRequest($request);

            if ($staffForm->isSubmitted() && $staffForm->isValid()) {
                if ($user->getEstablishment() !== $currentUser->getEstablishment()) {
                    $this->addFlash('error', 'Vous ne pouvez pas changer l\'établissement.');
                    return $this->redirectToRoute('training_parameters_user');
                }

                $existingRoles = $user->getRoles();
                $roles = ['ROLE_USER'];
                if (in_array('ROLE_ADMIN', $existingRoles, true)) {
                    $roles[] = 'ROLE_ADMIN';
                }
                if ($staffForm->get('isProfPrincipal')->getData()) {
                    $roles[] = 'ROLE_PT';
                }
                if ($staffForm->get('isTeamMember')->getData()) {
                    $roles[] = 'ROLE_TTM';
                }
                // Ne jamais vider les rôles personnel : retomber sur ceux déjà présents.
                if (!in_array('ROLE_PT', $roles, true) && !in_array('ROLE_TTM', $roles, true)) {
                    $roles = array_merge($roles, array_intersect(['ROLE_PT', 'ROLE_TTM'], $existingRoles));
                }
                $user->setRoles(array_unique($roles));
                $entityManager->flush();

                return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('user/edit.html.twig', [
                'user' => $user,
                'form' => $staffForm,
                'userType' => 'staff',
                'isStudent' => false,
                'activeContract' => null,
            ]);
        }

        // ---- Student edition ----
        $activeContract = $this->findActiveContract($user);

        $establishment = $currentUser->getEstablishment();
        $form = $this->createForm(UserType::class, $user, ['establishment' => $establishment]);

        $form->get('isApprentissage')->setData($user->isApprentissage());

        // Pre-fill tutor fields if active contract exists
        if ($activeContract && $activeContract->getTutor()) {
            $tutor = $activeContract->getTutor();
            $form->get('existingTutor')->setData($tutor);
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
            if ($user->getEstablishment() !== $currentUser->getEstablishment()) {
                $this->addFlash('error', 'Vous ne pouvez pas changer l\'établissement.');
                return $this->redirectToRoute('training_parameters_user');
            }

            // Un alternant conserve UNIQUEMENT son rôle : il ne peut jamais cumuler MEP/PT/Tuteur.
            $roles = ['ROLE_STUDENT', 'ROLE_USER'];
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $roles[] = 'ROLE_ADMIN';
            }

            $user->setRoles(array_unique($roles));

            // Update tutor and contract (the user is necessarily a student in this branch)
            $selectedTutor = $form->get('existingTutor')->getData();
            $tutorEmail = $form->get('tutorEmail')->getData();
            $tutor = null;

            if ($selectedTutor) {
                $tutor = $selectedTutor;
                $this->enableTutor($tutor);
                $entityManager->persist($tutor);
            } elseif ($tutorEmail) {
                $currentTutor = $activeContract?->getTutor();
                if ($currentTutor && $currentTutor->getEmail() === $tutorEmail) {
                    $tutor = $currentTutor;
                } else {
                    $tutor = $entityManager->getRepository(User::class)->findOneBy(['email' => $tutorEmail]);
                    if (!$tutor) {
                        $tutor = new User();
                        $tutor->setEmail($tutorEmail);
                        $tutor->setRoles(['ROLE_TUTOR', 'ROLE_USER']);
                        $tutor->setPassword('');
                    }
                    $this->enableTutor($tutor);
                }
                $tutor->setFirstName($form->get('tutorFirstName')->getData());
                $tutor->setLastName($form->get('tutorLastName')->getData());
                $tutor->setPhone($form->get('tutorPhone')->getData());
                $tutor->setCompanyName($form->get('companyName')->getData());
                $tutor->setCompanyAddress($form->get('companyAddress')->getData());
                $entityManager->persist($tutor);
            }

            if ($tutor) {
                if ($activeContract) {
                    $activeContract->setTutor($tutor);
                } else {
                    $tutorStudent = new TutorStudent();
                    $tutorStudent->setStudent($user);
                    $tutorStudent->setTutor($tutor);
                    $tutorStudent->setDateDebutContract($form->get('dateDebutContract')->getData() ?? new \DateTime());
                    $tutorStudent->setDateFinContract($form->get('dateFinContract')->getData() ?? (new \DateTime())->modify('+6 months'));
                    $entityManager->persist($tutorStudent);
                }
            }

            if ($activeContract) {
                $activeContract->setDateDebutContract($form->get('dateDebutContract')->getData());
                $activeContract->setDateFinContract($form->get('dateFinContract')->getData());
            }

            $entityManager->flush();

            return $this->redirectToRoute('training_parameters_user', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'userType' => 'student',
            'isStudent' => true,
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
    public function disable(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('disable'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('training_parameters_user');
        }

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

    #[Route('/{id}/toggle-apprentissage', name: 'app_user_toggle_apprentissage', methods: ['POST'])]
    public function toggleApprentissage(User $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('toggle_apprentissage', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $isApprentissage = filter_var($request->request->get('isApprentissage'), FILTER_VALIDATE_BOOLEAN);

        $user->setIsApprentissage($isApprentissage);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'isApprentissage' => $user->isApprentissage()
        ]);
    }

    /**
     * Return the student's currently active contract (start <= now <= end), or null.
     */
    private function findActiveContract(User $user): ?TutorStudent
    {
        $now = new \DateTime();
        foreach ($user->getStudentContracts() as $contract) {
            $startDate = $contract->getDateDebutContract();
            $endDate = $contract->getDateFinContract();

            if ((!$startDate || $startDate <= $now) && (!$endDate || $endDate >= $now)) {
                return $contract;
            }
        }

        return null;
    }

    private function enableTutor(User $tutor): void
    {
        if ($tutor->getDisabledAt() !== null) {
            $tutor->setDisabledAt(null);
        }
        if (!in_array('ROLE_TUTOR', $tutor->getRoles(), true)) {
            $tutor->setRoles(array_unique(array_merge($tutor->getRoles(), ['ROLE_TUTOR'])));
        }
    }
}