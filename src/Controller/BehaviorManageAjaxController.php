<?php

namespace App\Controller;

use App\Entity\BehaviorCriteria;
use App\Entity\BehaviorLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BehaviorManageAjaxController extends AbstractController
{
    #[Route('/behavior/level/toggle/{id}', name: 'app_behavior_level_toggle', methods: ['POST'])]
    public function toggleLevelBehavior(BehaviorLevel $level, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_TTM');

        // get the data
        $data = $request->toArray();
        $replacementLabel = $data['label_level_replacement'] ?? null;

        // verify data or throw
        if (empty($replacementLabel)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le label de remplacement est requis.'
            ], 400);
        }

        if (!$level) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Niveau introuvable.'
            ], 404);
        }

        if ($level->getDisabledAt() !== null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ce niveau est déjà désactivé.'
            ], 400);
        }

        // disable the odl level
        $level->setDisabledAt(new \DateTime());

        // create the replacement level
        $replacementLevel = new BehaviorLevel();
        $replacementLevel->setLabel(trim($replacementLabel));
        $replacementLevel->setLevelNumber($level->getLevelNumber());
        $replacementLevel->setBehaviorCriteria($level->getBehaviorCriteria());
        $replacementLevel->setDisabledAt(null);

        $em->persist($replacementLevel);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Niveau remplacé avec succès.',
            'old_level' => [
                'id' => $level->getId(),
                'label' => $level->getLabel(),
                'disabled_at' => $level->getDisabledAt()->format('Y-m-d H:i:s'),
            ],
            'new_level' => [
                'id' => $replacementLevel->getId(),
                'label' => $replacementLevel->getLabel(),
                'level_number' => $replacementLevel->getLevelNumber(),
                'criteria_id' => $replacementLevel->getBehaviorCriteria()->getId(),
            ]
        ]);
    }


    #[Route('/behavior/criteria/toggle/{id}', name: 'app_behavior_manage_ajax_create', methods: ['POST'])]
    public function toggleCriteriaBehavior(BehaviorCriteria $behaviorCriteria, EntityManagerInterface $em): JsonResponse
    {

        $this->denyAccessUnlessGranted('ROLE_TTM');

        if ($behaviorCriteria->getDisabledAt()) {
            $behaviorCriteria->setDisabledAt(null);
            $message = 'Criteria enable.';
        } else {
            $behaviorCriteria->setDisabledAt(new \DateTime());
            $message = 'Criteria disable.';
        }


        $em->flush();


        return new JsonResponse(['success' => true, 'message' => $message]);
    }


    #[Route('/behavior/manage/create', name: 'app_behavior_manage_ajax_create', methods: ['POST'])]
    public function createBehavior(Request $request, EntityManagerInterface $em): JsonResponse
    {

        $this->denyAccessUnlessGranted('ROLE_TTM');

        // get data from json
        $data = json_decode($request->getContent(), true);
        $data = $request->toArray();

        // verify behavior label or throw
        if (!isset($data['label_behavior']) || empty(trim($data['label_behavior']))) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Le libellé du comportement est requis.'
            ], 400);
        }

        // check all levels or throw
        for ($i = 1; $i <= 5; $i++) {
            $key = 'niveau' . $i;
            if (!isset($data[$key]) || empty(trim($data[$key]))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => "Le libellé du niveau {$i} est requis."
                ], 400);
            }
        }

        $label = trim($data['label_behavior']);
        $niveaux = [
            1 => trim($data['niveau1']),
            2 => trim($data['niveau2']),
            3 => trim($data['niveau3']),
            4 => trim($data['niveau4']),
            5 => trim($data['niveau5']),
        ];


        // create the criteria in db
        $criteria = new BehaviorCriteria();
        $criteria->setLabel($label);
        $criteria->setDisabledAt(null);

        $em->persist($criteria);

        // create the associated levels
        $levelsData = [];
        foreach ($niveaux as $levelNumber => $levelLabel) {
            if (!empty($levelLabel)) {
                $level = new BehaviorLevel();
                $level->setLabel($levelLabel);
                $level->setLevelNumber($levelNumber);
                $level->setBehaviorCriteria($criteria);
                $level->setDisabledAt(null);

                $em->persist($level);

                // prepare json to return
                $levelsData[] = [
                    'id' => null,
                    'label' => $levelLabel,
                    'level_number' => $levelNumber,
                ];
            }
        }

        $em->flush();

        //fill id after flush
        foreach ($criteria->getBehaviorLevels() as $i => $level) {
            $levelsData[$i]['id'] = $level->getId();
        }

        return new JsonResponse([
            'success' => true,
            'criteria' => [
                'id' => $criteria->getId(),
                'label' => $criteria->getLabel(),
                'levels' => $levelsData,
            ],
        ]);
    }
}
