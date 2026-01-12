<?php

namespace App\Controller;

use App\Repository\ClassroomRepository;
use App\Repository\DiplomaRepository;
use App\Repository\PeriodRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('app/trainings')]
class TrainingsController extends AbstractController
{
    #[Route('/training/parameters', name: 'training_parameters')]
    public function parameterRouting(): Response
    {
        // List of parameter routes in display order
        $routes = [
            'training_parameters_classroom',
            'training_parameters_diploma',
            'training_parameters_period',
            'training_parameters_schoolYear',
            'training_parameters_user',
        ];

        // Redirect to the first available parameters route
        return $this->redirectToRoute($routes[0]);
    }

    #[Route('/training/parameters/classroom', name: 'training_parameters_classroom')]
    public function parametersClassroom(ClassroomRepository $classroomRepository): Response
    {
        $classrooms = $classroomRepository->findBy([
            'disabledAt' => null,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'classroom',
            'classrooms' => $classrooms,
        ]);
    }

    #[Route('/training/parameters/diploma', name: 'training_parameters_diploma')]
    public function parametersDiploma(DiplomaRepository $diplomaRepository): Response
    {
        $diplomas = $diplomaRepository->findBy([
            'disabledAt' => null,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'diploma',
            'diplomas' => $diplomas,
        ]);
    }

    #[Route('/training/parameters/period', name: 'training_parameters_period')]
    public function parametersPeriod(PeriodRepository $periodRepository): Response
    {
        $periods = $periodRepository->findBy([
            'disabledAt' => null,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'period',
            'periods' => $periods,
        ]);
    }

    #[Route('/training/parameters/schoolYear', name: 'training_parameters_schoolYear')]
    public function parametersSchoolYear(SchoolYearRepository $schoolYearRepository): Response
    {
        $schoolYears = $schoolYearRepository->findBy([
            'disabledAt' => null,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'schoolYear',
            'school_years' => $schoolYears,
        ]);
    }

    #[Route('/training/parameters/user', name: 'training_parameters_user')]
    public function parametersUser(UserRepository $usersTrainingsRepository): Response
    {
        $users = $usersTrainingsRepository->findBy([
            'disabledAt' => null,
        ]);

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'user',
            'users' => $users,
        ]);
    }
}