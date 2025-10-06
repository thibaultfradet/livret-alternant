<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TTMListController extends AbstractController
{
    #[Route('/ttm/list', name: 'app_t_t_m_list')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $user = $this->getUser();
        
        $teacherListPath = sprintf('/uploads/classroom/teacher-list-%d.jpg', $user->getClassroom()->getId());

        return $this->render('ttm_list/index.html.twig', [
            'controller_name' => 'TTMListController',
            'teacherListFile' => file_exists($this->getParameter('kernel.project_dir') . '/public' . $teacherListPath )? $teacherListPath : null,
        ]);
    }
}
