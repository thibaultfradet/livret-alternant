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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class SkillManageController extends AbstractController
{
    public function __construct(private CsrfTokenManagerInterface $csrfTokenManager) {}

    #[Route('/skill-manage/{id?}', name: 'app_skill_management')]
    public function index(?int $id = null): Response
    {
        return $this->redirectToRoute('training_parameters_skill', $id ? ['diploma_id' => $id] : []);
    }



    #[Route('/skill-manage/level/create', name: 'app_skill_level_create', methods: ['GET','POST'])]
    public function createLevel(Request $request, EntityManagerInterface $em, SkillLevelRepository $skillLevelRepo): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $level = new SkillLevel();
        $level->setEstablishment($establishment);

        // Set order_index: last + 1, or 1 if none exist
        $lastLevel = $skillLevelRepo->findOneBy(
            ['establishment' => $establishment],
            ['order_index' => 'DESC']
        );
        $level->setOrderIndex($lastLevel ? $lastLevel->getOrderIndex() + 1 : 1);

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

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check diploma belongs to user's establishment
        if ($diploma->getEstablishment() !== $user->getEstablishment()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les diplômes de votre établissement.');
            return $this->redirectToRoute('app_skill_management');
        }

        $group = new SkillGroup();
        $group->setDiploma($diploma);

        $form = $this->createForm(SkillGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($group);
            $em->flush();
            $this->addFlash('success', 'Groupe créé avec succès.');
            return $this->redirectToRoute('training_parameters_skill', ['diploma_id' => $diploma->getId()]);
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
            return $this->redirectToRoute('training_parameters_skill', ['diploma_id' => $group->getDiploma()->getId()]);
        }

        return $this->render('skill_manage/form_skill.html.twig', [
            'form' => $form->createView(),
            'group' => $group
        ]);
    }


    #[Route('/skill-manage/group/{id}/rename', name: 'app_skill_group_rename', methods: ['POST'])]
    public function renameGroup(SkillGroup $group, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $token = new CsrfToken('rename_group_' . $group->getId(), $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('training_parameters_skill', ['diploma_id' => $group->getDiploma()->getId()]);
        }

        if ($group->getDiploma()->getEstablishment() !== $user->getEstablishment()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('training_parameters_skill');
        }

        $label = trim($request->request->get('label', ''));
        if ($label !== '') {
            $group->setLabel($label);
            $em->flush();
            $this->addFlash('success', 'Groupe renommé avec succès.');
        }

        return $this->redirectToRoute('training_parameters_skill', ['diploma_id' => $group->getDiploma()->getId()]);
    }

    #[Route('/skill-manage/skill/{id}/rename', name: 'app_skill_criteria_rename', methods: ['POST'])]
    public function renameCriteria(SkillCriteria $skill, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $token = new CsrfToken('rename_skill_' . $skill->getId(), $request->request->get('_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('training_parameters_skill', ['diploma_id' => $skill->getSkillGroup()->getDiploma()->getId()]);
        }

        if ($skill->getSkillGroup()->getDiploma()->getEstablishment() !== $user->getEstablishment()) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('training_parameters_skill');
        }

        $label = trim($request->request->get('label', ''));
        if ($label !== '') {
            $skill->setLabel($label);
            $em->flush();
            $this->addFlash('success', 'Compétence renommée avec succès.');
        }

        return $this->redirectToRoute('training_parameters_skill', ['diploma_id' => $skill->getSkillGroup()->getDiploma()->getId()]);
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

        return $this->redirectToRoute('training_parameters_skill', [
            'diploma_id' => $diplomaId
        ]);
    }


    // Toggle skill level status
    #[Route('/skill-level/{id}/toggle', name: 'app_skill_level_toggle')]
    public function toggleSkillLevel(
        SkillLevel $level,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Check if level belongs to user's establishment
        if ($level->getEstablishment() !== $user->getEstablishment()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les niveaux de compétence de votre établissement.');
            return $this->redirectToRoute('app_skill_level_manage');
        }

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
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        return $this->render('skill_manage/level_manage.html.twig', [
            'levels' => $repository->findBy(
                [
                    'disabledAt' => null,
                    'establishment' => $establishment
                ],
                ['order_index' => 'ASC']
            ),
            'diploma' => $diplomaRepository->findOneBy([]), // diplôme courant si nécessaire
        ]);
    }


    #[Route('/skill-level/reorder', name: 'app_skill_level_reorder', methods: ['POST'])]
    public function reorderSkillLevels(
        Request $request,
        EntityManagerInterface $em,
        SkillLevelRepository $repository
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $data = json_decode($request->getContent(), true);

        if (!isset($data['order'])) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        foreach ($data['order'] as $item) {
            $level = $repository->find($item['id']);

            // Security: ensure level belongs to user's establishment
            if (!$level || $level->getEstablishment() !== $establishment) {
                continue;
            }

            // Update position
            $level->setOrderIndex($item['position']);
        }

        $em->flush();

        return new JsonResponse(['status' => 'ok']);
    }
}
