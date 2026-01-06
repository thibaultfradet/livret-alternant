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
        $user = $this->getUser();

        if (!$this->canDisplayClassroomData($user)) {
            return $this->render('classroom_info/ttm_list.html.twig', [
                'hasData' => false,
            ]);
        }

        $teacherListPath = sprintf(
            '/uploads/classroom/teacher-list-%d.png',
            $user->getClassroom()->getId()
        );

        $teacherListExists = file_exists(
            $this->getParameter('kernel.project_dir') . '/public' . $teacherListPath
        );

        return $this->render('classroom_info/ttm_list.html.twig', [
            'hasData' => $teacherListExists,
            'teacherListFile' => $teacherListExists ? $teacherListPath : null,
        ]);
    }

    #[Route('/calendar-schedule', name: 'app_calendar_schedule')]
    public function calendarSchedule(): Response
    {
        $user = $this->getUser();

        if (!$this->canDisplayClassroomData($user)) {
            return $this->render('classroom_info/calendar_schedule.html.twig', [
                'hasData' => false,
            ]);
        }

        $classroomId = $user->getClassroom()->getId();

        $calendarPath = sprintf('/uploads/classroom/calendar-%d.png', $classroomId);
        $schedulePath = sprintf('/uploads/classroom/schedule-%d.png', $classroomId);

        $calendarExists = file_exists(
            $this->getParameter('kernel.project_dir') . '/public' . $calendarPath
        );

        $scheduleExists = file_exists(
            $this->getParameter('kernel.project_dir') . '/public' . $schedulePath
        );

        return $this->render('classroom_info/calendar_schedule.html.twig', [
            'hasData' => $calendarExists || $scheduleExists,
            'calendarFile' => $calendarExists ? $calendarPath : null,
            'scheduleFile' => $scheduleExists ? $schedulePath : null,
        ]);
    }


    private function canDisplayClassroomData($user): bool
    {
        if (!$user) {
            return false;
        }

        $classroom = $user->getClassroom();
        if (!$classroom) {
            return false;
        }

        $schoolYear = $classroom->getSchoolYear();
        if (!$schoolYear) {
            return false;
        }

        // Business rule: only active school year
        return $schoolYear->isActive() === true;
    }
}
