<?php

namespace App\Controller;

use App\Entity\BehaviorCriteria;
use App\Entity\BehaviorLevel;
use App\Form\BehaviorLevelReplacementType;
use App\Form\CreateBehaviorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TTM')]
final class BehaviorManageController extends AbstractController
{
    use EstablishmentGuardTrait;


    #[Route('/behavior-manage/create', name: 'training_parameters_behavior_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $establishment = $user->getEstablishment();

        $form = $this->createForm(CreateBehaviorType::class);
        $form->handleRequest($request);

        // Process only if submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            // Create behavior criteria
            $criteria = new BehaviorCriteria();
            $criteria->setLabel(trim($data['label_behavior']));
            $criteria->setDisabledAt(null);
            $criteria->setEstablishment($establishment);

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

            return $this->redirectToRoute('training_parameters_behavior');
        }

        return $this->render('behavior_manage/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/behavior/level/replace/{id}', name: 'app_behavior_level_replace', methods: ['GET','POST'])]
    public function replaceLevel(BehaviorLevel $level, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->denyIfForeignEstablishment($level, 'training_parameters_behavior', 'Vous ne pouvez modifier que les niveaux de comportement de votre établissement.')) {
            return $redirect;
        }

        if ($level->getDisabledAt() !== null) {
            $this->addFlash('warning', 'Ce niveau est déjà désactivé.');
            return $this->redirectToRoute('training_parameters_behavior');
        }

        $form = $this->createForm(BehaviorLevelReplacementType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $newLabel = $data['label_level_replacement'];

            // Disable old level
            $level->setDisabledAt(new \DateTime());

            // Create replacement
            $replacementLevel = new BehaviorLevel();
            $replacementLevel->setLabel(trim($newLabel));
            $replacementLevel->setLevelNumber($level->getLevelNumber());
            $replacementLevel->setBehaviorCriteria($level->getBehaviorCriteria());
            $replacementLevel->setDisabledAt(null);

            $em->persist($replacementLevel);
            $em->flush();

            $this->addFlash('success', sprintf('Le niveau "%s" a été remplacé par "%s".', $level->getLabel(), $replacementLevel->getLabel()));

            return $this->redirectToRoute('training_parameters_behavior');
        }

        return $this->render('behavior_manage/replace_level.html.twig', [
            'level' => $level,
            'form' => $form->createView(),
        ]);
    }



    #[Route('/behavior/manage/disable/{id}', name: 'app_behavior_disable', methods: ['POST'])]
    public function disableBehavior(Request $request, BehaviorCriteria $behavior, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('disable'.$behavior->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('training_parameters_behavior');
        }

        if ($redirect = $this->denyIfForeignEstablishment($behavior, 'training_parameters_behavior', 'Vous ne pouvez modifier que les critères de comportement de votre établissement.')) {
            return $redirect;
        }

        if ($behavior->getDisabledAt() !== null) {
            $this->addFlash('warning', 'Ce comportement est déjà désactivé.');
            return $this->redirectToRoute('training_parameters_behavior');
        }

        // Disable the behavior criteria
        $behavior->setDisabledAt(new \DateTime());

        // Optionally, disable all associated levels
        foreach ($behavior->getBehaviorLevels() as $level) {
            if ($level->getDisabledAt() === null) {
                $level->setDisabledAt(new \DateTime());
                $em->persist($level);
            }
        }

        $em->persist($behavior);
        $em->flush();

        $this->addFlash('success', 'Le comportement a été supprimé avec succès.');

        return $this->redirectToRoute('training_parameters_behavior');
    }
}
