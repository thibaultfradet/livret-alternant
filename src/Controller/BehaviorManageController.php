<?php

namespace App\Controller;

use App\Entity\BehaviorCriteria;
use App\Entity\BehaviorLevel;
use App\Form\CreateBehaviorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class BehaviorManageController extends AbstractController
{
    #[Route('/behavior-manage', name: 'app_behavior_manage')]
    public function index(EntityManagerInterface $em): Response
    {

        // get all active behavior with their levels
        $query = $em->createQueryBuilder()
            ->select('bc', 'bl')
            ->from(BehaviorCriteria::class, 'bc')
            ->leftJoin('bc.behaviorLevels', 'bl')
            ->where('bc.disabledAt IS NULL')
            ->andWhere('bl.disabledAt IS NULL OR bl.disabledAt IS NULL')
            ->orderBy('bc.label', 'ASC')
            ->addOrderBy('bl.levelNumber', 'ASC')
            ->getQuery();

        $behaviors = $query->getResult();

        return $this->render('behavior_manage/index.html.twig', [
            'controller_name' => 'BehaviorManageController',
            'behaviors' => $behaviors,
        ]);
    }

   

   #[Route('/behavior-manage/create', name: 'app_behavior_manage_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CreateBehaviorType::class);
        $form->handleRequest($request);

        // Process only if submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            // Create behavior criteria
            $criteria = new BehaviorCriteria();
            $criteria->setLabel(trim($data['label_behavior']));
            $criteria->setDisabledAt(null);

            $em->persist($criteria);

            // Create behavior levels
            for ($i = 1; $i <= 5; $i++) {
                $level = new BehaviorLevel();
                $level->setLabel(trim($data['niveau' . $i]));
                $level->setLevelNumber($i);
                $level->setBehaviorCriteria($criteria);
                $level->setDisabledAt(null);

                $em->persist($level);
            }

            $em->flush();

            $this->addFlash('success', 'Le comportement et ses niveaux ont été créés avec succès.');

            return $this->redirectToRoute('app_behavior_manage');
        }

        return $this->render('behavior_manage/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
