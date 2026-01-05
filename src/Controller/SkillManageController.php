<?php

namespace App\Controller;

use App\Entity\Diploma;
use App\Entity\SkillCriteria;
use App\Entity\SkillGroup;
use App\Entity\SkillLevel;
use App\Form\SkillCriteriaType;
use App\Form\SkillGroupType;
use App\Form\SkillLevelType;
use App\Repository\DiplomaRepository;
use App\Repository\SkillLevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SkillManageController extends AbstractController
{
    #[Route('/skill-manage/{id?}', name: 'app_skill_management')]
    public function index(DiplomaRepository $diplomaRepository, SkillLevelRepository $skillLevelRepo, ?int $id = null): Response
    {

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



    #[Route('/skill-manage/level/create', name: 'app_skill_level_create', methods: ['GET','POST'])]
    public function createLevel(Request $request, EntityManagerInterface $em): Response
    {
        $level = new SkillLevel();
        $form = $this->createForm(SkillLevelType::class, $level);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($level);
            $em->flush();
            $this->addFlash('success', 'Niveau créé avec succès.');
            return $this->redirectToRoute('app_skill_management', ['id' => $level->getId()]);
        }

        return $this->render('skill/form_level.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/skill-manage/group/create/{diploma}', name: 'app_skill_group_create', methods: ['GET','POST'])]
    public function createGroup(Diploma $diploma, Request $request, EntityManagerInterface $em): Response
    {
        $group = new SkillGroup();
        $group->setDiploma($diploma);

        $form = $this->createForm(SkillGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($group);
            $em->flush();
            $this->addFlash('success', 'Groupe créé avec succès.');
            return $this->redirectToRoute('app_skill_management', ['id' => $diploma->getId()]);
        }

        return $this->render('skill/form_group.html.twig', [
            'form' => $form->createView(),
            'diploma' => $diploma
        ]);
    }

    #[Route('/skill-manage/skill/create/{group}', name: 'app_skill_criteria_create', methods: ['GET','POST'])]
    public function createSkill(SkillGroup $group, Request $request, EntityManagerInterface $em): Response
    {
        $skill = new SkillCriteria();
        $skill->setSkillGroup($group);

        $form = $this->createForm(SkillCriteriaType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($skill);
            $em->flush();
            $this->addFlash('success', 'Compétence créée avec succès.');
            return $this->redirectToRoute('app_skill_management', ['id' => $group->getDiploma()->getId()]);
        }

        return $this->render('skill/form_skill.html.twig', [
            'form' => $form->createView(),
            'group' => $group
        ]);
    }

    // Toggle skill / group / level status
    #[Route('/skill-manage/{type}/{id}/toggle', name: 'app_toggle_status', methods: ['POST'])]
    public function toggleStatus(string $type, int $id, EntityManagerInterface $em): Response
    {
        switch ($type) {
            case 'skill':
                $entity = $em->getRepository(SkillCriteria::class)->find($id);
                break;
            case 'group':
                $entity = $em->getRepository(SkillGroup::class)->find($id);
                break;
            case 'level':
                $entity = $em->getRepository(SkillLevel::class)->find($id);
                break;
            default:
                throw $this->createNotFoundException('Type invalide');
        }

        if (!$entity) throw $this->createNotFoundException('Entité introuvable');

        $entity->setDisabledAt($entity->getDisabledAt() ? null : new \DateTime());
        $em->flush();

        $this->addFlash('success', ucfirst($type) . ' mis à jour.');
        return $this->redirectToRoute('app_skill_management', ['id' => $type === 'group' ? $entity->getDiploma()->getId() : $entity->getSkillGroup()->getDiploma()->getId()]);
    }

}
