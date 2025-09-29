<?php

namespace App\Controller;

use App\Entity\BehaviorCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class BehaviorManageController extends AbstractController
{
    #[Route('/behavior-manage', name: 'app_behavior_manage')]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_TTM');

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
}
