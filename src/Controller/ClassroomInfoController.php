<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClassroomInfoController extends AbstractController
{
    #[Route('/teaching-team-list', name: 'app_ttm_list')]
    public function ttmList(): Response
    {
        $classroom = $this->activeClassroom();

        if (!$classroom) {
            return $this->render('classroom_info/ttm_list.html.twig', [
                'hasData' => false,
            ]);
        }

        $teacherListPath = sprintf(
            '/uploads/classroom/teacher-list-%d.png',
            $classroom->getId()
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
    public function calendarSchedule(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $tutorStudents = array_filter(
            array_map(fn($c) => $c->getStudent(), $user?->getTutorContracts()->toArray() ?? [])
        );
        $tutorStudents = array_values($tutorStudents);

        if ($tutorStudents) {
            $selectedId = (int) $request->query->get('student', $tutorStudents[0]->getId());
            $selected = current(array_filter($tutorStudents, fn($s) => $s->getId() === $selectedId));

            // Reject attempts to view a student the tutor doesn't own
            if (!$selected) {
                throw $this->createAccessDeniedException();
            }

            $selectedId = $selected->getId();
            $classroom = $selected->getClassroom();
            if (!$classroom?->getSchoolYear()?->isActive()) {
                $classroom = null;
            }
        } else {
            $selectedId = null;
            $classroom = $this->activeClassroom();
        }

        if (!$classroom) {
            return $this->render('classroom_info/calendar_schedule.html.twig', [
                'hasData' => false,
                'tutorStudents' => $tutorStudents,
                'selectedStudentId' => $selectedId,
            ]);
        }

        $classroomId = $classroom->getId();

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
            'tutorStudents' => $tutorStudents,
            'selectedStudentId' => $selectedId,
        ]);
    }

    private function activeClassroom(): ?Classroom
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $classroom = $user?->getEffectiveClassroom();

        return $classroom?->getSchoolYear()?->isActive() ? $classroom : null;
    }
}
