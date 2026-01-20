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

        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        // Filter by establishment
        $diplomasQb = $diplomaRepository->createQueryBuilder('d')
            ->where('d.disabledAt IS NULL')
            ->andWhere('d.Establishment = :establishment')
            ->setParameter('establishment', $establishment)
            ->getQuery();

        $diplomas = $diplomasQb->getResult();

        if (empty($diplomas)) {
            throw $this->createNotFoundException('Aucun diplôme n\'est disponible pour votre établissement.');
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
    

        return $this->render('skill_manage/index.html.twig', [
            'diplomas' => $diplomas,
            'selectedDiploma' => $selectedDiploma,
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
            return $this->redirectToRoute('app_skill_level_manage');
        }

        return $this->render('skill_manage/form_level.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/skill-manage/group/create/{diploma}', name: 'app_skill_group_create', methods: ['GET','POST'])]
    public function createGroup(Diploma $diploma, Request $request, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();

        // Check diploma belongs to user's establishment
        if ($diploma->getEstablishment() !== $user->getEstablishment()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que les diplômes de votre établissement.');
        }

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

        return $this->render('skill_manage/form_group.html.twig', [
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

        return $this->render('skill_manage/form_skill.html.twig', [
            'form' => $form->createView(),
            'group' => $group
        ]);
    }


    // Toggle skill or group status (no levels here)
    #[Route('/skill-manage/{type}/{id}/toggle', name: 'app_toggle_status')]
    public function toggleStatus(
        string $type,
        int $id,
        EntityManagerInterface $em
    ): Response {

        switch ($type) {
            case 'skill':
                $entity = $em->getRepository(SkillCriteria::class)->find($id);
                break;

            case 'group':
                $entity = $em->getRepository(SkillGroup::class)->find($id);
                break;

            default:
                throw $this->createNotFoundException('Invalid type');
        }

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        // Toggle disabledAt status
        $entity->setDisabledAt(
            $entity->getDisabledAt() ? null : new \DateTime()
        );

        $em->flush();

        $this->addFlash('success', "L'objet a été mis à jour avec succès.");

        // Redirect back to skill management
        $diplomaId = $type === 'group'
            ? $entity->getDiploma()->getId()
            : $entity->getSkillGroup()->getDiploma()->getId();

        return $this->redirectToRoute('app_skill_management', [
            'id' => $diplomaId
        ]);
    }


    // Toggle skill level status
    #[Route('/skill-level/{id}/toggle', name: 'app_skill_level_toggle')]
    public function toggleSkillLevel(
        SkillLevel $level,
        EntityManagerInterface $em
    ): Response {

        if (!$level) {
            throw $this->createNotFoundException('Level not found');
        }

        // Toggle disabledAt
        $level->setDisabledAt(
            $level->getDisabledAt() ? null : new \DateTime()
        );

        $em->flush();

        $this->addFlash('success', 'Niveau mis à jour avec succès.');

        // Redirect back to level management page
        return $this->redirectToRoute('app_skill_level_manage');
    }


    #[Route('/skill-levels', name: 'app_skill_level_manage')]
    public function manageLevels(
        SkillLevelRepository $repository,
        DiplomaRepository $diplomaRepository
    ): Response {
        return $this->render('skill_manage/level_manage.html.twig', [
            'levels' => $repository->findBy(
                ['disabledAt' => null],
                ['label' => 'ASC']
            ),
            'diploma' => $diplomaRepository->findOneBy([]), // diplôme courant si nécessaire
        ]);
    }
}
