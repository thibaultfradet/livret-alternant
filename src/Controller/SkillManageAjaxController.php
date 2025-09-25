<?php

namespace App\Controller;

use App\Entity\Diploma;
use App\Entity\SkillCriteria;
use App\Entity\SkillGroup;
use App\Entity\SkillLevel;
use App\Repository\SkillLevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/skill-manage')]
class SkillManageAjaxController extends AbstractController
{
    // toggle skill criteria 
    #[Route('/skill/{id}/toggle', name: 'ajax_skill_toggle', methods: ['POST'])]
    public function toggleSkill(SkillCriteria $skill, EntityManagerInterface $em): JsonResponse
    {
        if ($skill->getDisabledAt()) {
            $skill->setDisabledAt(null);
            $message = 'Criteria enable.';
        } else {
            $skill->setDisabledAt(new \DateTime());
            $message = 'Criteria disable.';
        }
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => $message]);
    }

    // save skill criteria
    #[Route('/skill/save/{skillGroup}', name: 'ajax_skill_criteria_save', methods: ['POST'])]
    public function saveSkillCriteria(SkillGroup $skillGroup, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['label'])) {
            return new JsonResponse(['success' => false, 'message' => 'Le libellé est manquant.'], 400);
        }

        $criteria = new SkillCriteria();
        $criteria->setLabel($data['label']);
        $criteria->setSkillGroup($skillGroup);

        $em->persist($criteria);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Skill criteria added.',
            'skill' => ['id' => $criteria->getId(), 'label' => $criteria->getLabel()]
        ]);
    }

    // toggle skill group 
    #[Route('/group/{id}/toggle', name: 'ajax_skill_group_toggle', methods: ['POST'])]
    public function toggleGroupStatus(SkillGroup $skillGroup, EntityManagerInterface $em): JsonResponse
    {
        if ($skillGroup->getDisabledAt()) {
            $skillGroup->setDisabledAt(null);
            $message = 'Group enable.';
        } else {
            $skillGroup->setDisabledAt(new \DateTime());
            $message = 'Group disable.';
        }
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => $message]);
    }

    // create skill group
    #[Route('/group/save/{diploma}', name: 'ajax_skill_group_save', methods: ['POST'])]
    public function saveSkillGroup(Diploma $diploma, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data['label'])) {
            return new JsonResponse(['success' => false, 'message' => 'Le libellé est manquant.'], 400);
        }

        $group = new SkillGroup();
        $group->setLabel($data['label']);
        $group->setDiploma($diploma);

        $em->persist($group);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Groupe créé.',
            'group' => ['id' => $group->getId(), 'label' => $group->getLabel()]
        ]);
    }


    // save levels
    #[Route('/level/save', name: 'ajax_skill_levels_save', methods: ['POST'])]
    public function saveLevels(
        Request $request,
        SkillLevelRepository $levelRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['label']) || empty(trim($data['label']))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le libellé du niveau est requis.'
            ], 400);
        }

        $label = trim($data['label']);

        // verify the existence in db
        $existingLevel = $levelRepository->findOneBy(['label' => $label]);
        if ($existingLevel) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce niveau existe déjà.'
            ], 409);
        }

        // create the level
        $level = new SkillLevel();
        $level->setLabel($label);

        $em->persist($level);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Level created.',
            'level' => [
                'id' => $level->getId(),
                'label' => $level->getLabel()
            ]
        ]);
    }


    // toggle skill level 
    #[Route('/level/{id}/toggle', name: 'ajax_skill_level_toggle', methods: ['POST'])]
    public function toggleLevelStatus(SkillLevel $skillLevel, EntityManagerInterface $em): JsonResponse
    {
        if ($skillLevel->getDisabledAt()) {
            $skillLevel->setDisabledAt(null);
            $message = 'Level enable.';
        } else {
            $skillLevel->setDisabledAt(new \DateTime());
            $message = 'Level disable.';
        }
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => $message]);
    }
}
