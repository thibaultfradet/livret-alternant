<?php

namespace App\Controller;

use App\Entity\BehaviorCriteria;
use App\Repository\ClassroomRepository;
use App\Repository\DiplomaRepository;
use App\Repository\PeriodRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrainingsController extends AbstractController
{
    #[Route('/parameters', name: 'training_parameters')]
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

    #[Route('/parameters/classroom', name: 'training_parameters_classroom')]
    public function parametersClassroom(ClassroomRepository $classroomRepository): Response
    {
        $classrooms = $classroomRepository->findAll();

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'classroom',
            'classrooms' => $classrooms,
        ]);
    }

    #[Route('/parameters/diploma', name: 'training_parameters_diploma')]
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

    #[Route('/parameters/period', name: 'training_parameters_period')]
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

    #[Route('/parameters/schoolYear', name: 'training_parameters_schoolYear')]
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



    #[Route('/parameters/user', name: 'training_parameters_behavior')]
    public function index(EntityManagerInterface $em): Response
    {
        // get all active behavior with their levels
        $query = $em->createQueryBuilder()
            ->select('bc', 'bl')
            ->from(BehaviorCriteria::class, 'bc')
            ->leftJoin('bc.behaviorLevels', 'bl')
            ->where('bc.disabledAt IS NULL')
            ->andWhere('bl.disabledAt IS NULL OR bl.disabledAt IS NULL')
            ->orderBy('bc.label', 'ASC')
            ->addOrderBy('bl.levelNumber', 'ASC')
            ->getQuery();

        $behaviors = $query->getResult();

        return $this->render('trainings/parameters.html.twig', [
            'menuTrainings' => 'active',
            'currentTab' => 'behavior',
            'behaviors' => $behaviors,
        ]);
    }



    #[Route('/parameters/user', name: 'training_parameters_user')]
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