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
        // format ttm roles
        $ttmRoles = [];
        foreach ($user->getClassroom()->getTtmClassrooms() as $ttmClassroom) {
            $ttm = $ttmClassroom->getTtm();
            $ttmId = $ttm->getId();

            if (!isset($ttmRoles[$ttmId])) {
                $ttmRoles[$ttmId] = [
                    'name' => $ttm->getFirstName() . ' ' . $ttm->getLastName(),
                    'roles' => [],
                ];
            }
            $ttmRoles[$ttmId]['roles'][] = $ttmClassroom->getLabel();
        }


        return $this->render('ttm_list/index.html.twig', [
            'controller_name' => 'TTMListController',
            'ttmRoles' => $ttmRoles,
        ]);
    }
}
