<?php

namespace App\Controller;

use App\Repository\DiplomaRepository;
use App\Repository\SkillLevelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SkillManageController extends AbstractController
{
    #[Route('/skill-manage/{id?}', name: 'app_skill_management')]
    public function index(DiplomaRepository $diplomaRepository, SkillLevelRepository $skillLevelRepo, ?int $id = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TTM');

        // get all diplomas
        $diplomas = $diplomaRepository->findAll();

        if (empty($diplomas)) {
            throw $this->createNotFoundException('Aucun diplôme n\'est disponible.');
        }

        // if no parameter diploma take the first one of all
        if (!$id) {
            $selectedDiploma = $diplomas[0];
        } else {
            $selectedDiploma = $diplomaRepository->findWithSkills($id);
            if (!$selectedDiploma) {
                throw $this->createNotFoundException('Le diplôme n\'existe pas.');
            }
        }


        $levels = $skillLevelRepo->findAll();

        return $this->render('skill_manage/index.html.twig', [
            'diplomas' => $diplomas,
            'selectedDiploma' => $selectedDiploma,
            'levels' => $levels,
        ]);
    }
}
