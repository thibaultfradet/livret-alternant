<?php

namespace App\Controller;

use App\Entity\BehaviorCriteria;
use App\Entity\User;
use App\Repository\ClassroomRepository;
use App\Repository\DiplomaRepository;
use App\Repository\PeriodRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[IsGranted('ROLE_TTM')]
class TrainingsController extends AbstractController
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerInterface $mailer
    ) {}

    #[Route('/parameters', name: 'training_parameters')]
    public function parameterRouting(): Response
    {
        // List of parameter routes in display order
        $routes = [
            'training_parameters_classroom',
            'training_parameters_diploma',
            'training_parameters_period',
            'training_parameters_schoolYear',
            'training_parameters_behavior',
            'training_parameters_user',
        ];

        // Redirect to the first available parameters route
        return $this->redirectToRoute($routes[0]);
    }

    #[Route('/parameters/classroom', name: 'training_parameters_classroom')]
    public function parametersClassroom(
        Request $request,
        ClassroomRepository $classroomRepository,
        SchoolYearRepository $schoolYearRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();
        $activeSchoolYear = $schoolYearRepository->findActiveByEstablishment($establishment);

        $allSchoolYears = $schoolYearRepository->findBy(
            ['disabledAt' => null, 'establishment' => $establishment],
            ['startDate' => 'DESC']
        );

        $yearId = $request->query->getInt('school_year_id');
        $selectedSchoolYear = $yearId
            ? ($schoolYearRepository->find($yearId) ?? $activeSchoolYear)
            : $activeSchoolYear;

        $classrooms = $classroomRepository->findBy(['schoolYear' => $selectedSchoolYear]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'classroom',
            'classrooms' => $classrooms,
            'activeSchoolYear' => $activeSchoolYear,
            'selectedSchoolYear' => $selectedSchoolYear,
            'allSchoolYears' => $allSchoolYears,
        ]);
    }

    #[Route('/parameters/diploma', name: 'training_parameters_diploma')]
    public function parametersDiploma(
        DiplomaRepository $diplomaRepository,
        SchoolYearRepository $schoolYearRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $diplomas = $diplomaRepository->findBy([
            'disabledAt' => null,
            'establishment' => $establishment,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'diploma',
            'diplomas' => $diplomas,
            'activeSchoolYear' => $schoolYearRepository->findActiveByEstablishment($establishment),
        ]);
    }

    #[Route('/parameters/period', name: 'training_parameters_period')]
    public function parametersPeriod(
        Request $request,
        PeriodRepository $periodRepository,
        SchoolYearRepository $schoolYearRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();
        $activeSchoolYear = $schoolYearRepository->findActiveByEstablishment($establishment);

        $allSchoolYears = $schoolYearRepository->findBy(
            ['disabledAt' => null, 'establishment' => $establishment],
            ['startDate' => 'DESC']
        );

        $yearId = $request->query->getInt('school_year_id');
        $selectedSchoolYear = $yearId
            ? ($schoolYearRepository->find($yearId) ?? $activeSchoolYear)
            : $activeSchoolYear;

        $periods = $periodRepository->findBy([
            'schoolYear' => $selectedSchoolYear,
            'disabledAt' => null,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'period',
            'periods' => $periods,
            'activeSchoolYear' => $activeSchoolYear,
            'selectedSchoolYear' => $selectedSchoolYear,
            'allSchoolYears' => $allSchoolYears,
        ]);
    }

    #[Route('/parameters/schoolYear', name: 'training_parameters_schoolYear')]
    public function parametersSchoolYear(SchoolYearRepository $schoolYearRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $schoolYears = $schoolYearRepository->findBy(
            ['disabledAt' => null, 'establishment' => $establishment],
            ['startDate' => 'DESC']
        );

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'schoolYear',
            'school_years' => $schoolYears,
            'activeSchoolYear' => $schoolYearRepository->findActiveByEstablishment($establishment),
        ]);
    }

    #[Route('/parameters/behavior', name: 'training_parameters_behavior')]
    public function index(EntityManagerInterface $em, SchoolYearRepository $schoolYearRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $query = $em->createQueryBuilder()
            ->select('bc', 'bl')
            ->from(BehaviorCriteria::class, 'bc')
            ->leftJoin('bc.behaviorLevels', 'bl')
            ->where('bc.disabledAt IS NULL')
            ->andWhere('bc.establishment = :establishment')
            ->andWhere('bl.disabledAt IS NULL OR bl.disabledAt IS NULL')
            ->setParameter('establishment', $establishment)
            ->orderBy('bc.label', 'ASC')
            ->addOrderBy('bl.levelNumber', 'ASC')
            ->getQuery();

        $behaviors = $query->getResult();

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'behavior',
            'behaviors' => $behaviors,
            'activeSchoolYear' => $schoolYearRepository->findActiveByEstablishment($establishment),
        ]);
    }


    #[Route('/parameters/skills', name: 'training_parameters_skill')]
    public function parametersSkill(
        Request $request,
        DiplomaRepository $diplomaRepository,
        SchoolYearRepository $schoolYearRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $diplomas = $diplomaRepository->findActiveByEstablishment($establishment);

        if (empty($diplomas)) {
            return $this->render('trainings/parameters.html.twig', [
                'menuTrainings' => 'active',
                'currentTab' => 'skill',
                'diplomas' => [],
                'selectedDiploma' => null,
                'activeSchoolYear' => $schoolYearRepository->findActiveByEstablishment($establishment),
            ]);
        }

        $diplomaId = $request->query->getInt('diploma_id');
        if ($diplomaId) {
            $selectedDiploma = $diplomaRepository->findWithSkills($diplomaId);
            if (!$selectedDiploma || $selectedDiploma->getEstablishment() !== $establishment) {
                $selectedDiploma = $diplomaRepository->findWithSkills($diplomas[0]->getId());
            }
        } else {
            $selectedDiploma = $diplomaRepository->findWithSkills($diplomas[0]->getId());
        }

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'skill',
            'diplomas' => $diplomas,
            'selectedDiploma' => $selectedDiploma,
            'activeSchoolYear' => $schoolYearRepository->findActiveByEstablishment($establishment),
        ]);
    }

    #[Route('/parameters/user', name: 'training_parameters_user')]
    public function parametersUser(
        UserRepository $usersTrainingsRepository,
        SchoolYearRepository $schoolYearRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $users = $usersTrainingsRepository->findUsersForEstablishment($establishment);

        // Separate students and tutors for the connexion link modal
        $sendableUsers = array_filter($users, function (User $u) {
            $roles = $u->getCleanedRoles();
            return in_array('ROLE_STUDENT', $roles) || in_array('ROLE_TUTOR', $roles);
        });

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'user',
            'users' => $users,
            'sendableUsers' => array_values($sendableUsers),
            'activeSchoolYear' => $schoolYearRepository->findActiveByEstablishment($establishment),
        ]);
    }

    #[Route('/parameters/user/send-welcome-emails', name: 'training_parameters_send_welcome_emails', methods: ['POST'])]
    public function sendWelcomeEmails(Request $request, UserRepository $userRepository): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $establishment = $currentUser->getEstablishment();

        if (!$this->isCsrfTokenValid('send_welcome_emails', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('training_parameters_user');
        }

        $userIds = array_map('intval', $request->request->all('user_ids'));
        if (empty($userIds)) {
            $this->addFlash('warning', 'Aucun utilisateur sélectionné.');
            return $this->redirectToRoute('training_parameters_user');
        }

        $establishmentName = $establishment ? $establishment->getName() : 'votre centre de formation';
        $candidates = $userRepository->findUsersForEstablishment($establishment);
        $candidateMap = [];
        foreach ($candidates as $c) {
            $candidateMap[$c->getId()] = $c;
        }

        $sent = 0;
        $errors = 0;

        foreach ($userIds as $userId) {
            $user = $candidateMap[$userId] ?? null;
            if (!$user) {
                continue;
            }

            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                $email = (new TemplatedEmail())
                    ->from(new Address('test@example.com', 'Livret de l\'alternant'))
                    ->to((string) $user->getEmail())
                    ->subject("Bienvenue sur le Livret de l'Alternant — Définissez votre mot de passe")
                    ->htmlTemplate('emails/welcome.html.twig')
                    ->context([
                        'resetToken' => $resetToken,
                        'firstName' => $user->getFirstName(),
                        'establishment' => $establishmentName,
                    ]);

                $this->mailer->send($email);
                $sent++;
            } catch (ResetPasswordExceptionInterface $e) {
                $errors++;
            }
        }

        if ($sent > 0) {
            $this->addFlash('success', $sent . ' email(s) de bienvenue envoyé(s) avec succès.');
        }
        if ($errors > 0) {
            $this->addFlash('warning', $errors . ' email(s) n\'ont pas pu être envoyés (token déjà actif ou autre erreur).');
        }

        return $this->redirectToRoute('training_parameters_user');
    }
}