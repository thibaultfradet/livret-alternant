<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClassroomInfoController extends AbstractController
{
    #[Route('/teaching-team-list', name: 'app_ttm_list')]
    public function ttmList(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $user = $this->getUser();
        
        $teacherListPath = sprintf('/uploads/classroom/teacher-list-%d.jpg', $user->getClassroom()->getId());

        return $this->render('classroom_info/ttm_list.html.twig', [
            'controller_name' => 'ClassroomInfoController',
            'teacherListFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $teacherListPath )? $teacherListPath : null,
        ]);
    }

     #[Route('/calendar-schedule', name: 'app_calendar_schedule')]
    public function calendar_schedule(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $user = $this->getUser();
        
        $calendarPath = sprintf('/uploads/classroom/calendar-%d.jpg', $user->getClassroom()->getId());
        $schedulePath = sprintf('/uploads/classroom/schedule-%d.jpg', $user->getClassroom()->getId());


        return $this->render('classroom_info/calendar_schedule.html.twig', [
            'controller_name' => 'ClassroomInfoController',
            'calendarFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $calendarPath )? $calendarPath : null,
            'scheduleFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $schedulePath )? $schedulePath : null,
        ]);
    }
}
