<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TermsAcceptance;
use App\Repository\SchoolYearRepository;
use App\Repository\TermsAcceptanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
   use App\Form\TermsAcceptanceType;

final class TermsAcceptanceController extends AbstractController
{
    
    #[Route('/terms-acceptance', name: 'app_terms_acceptance')]
    public function index(Request $request, SchoolYearRepository $schoolYearRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $activeYear = $schoolYearRepository->findActiveByEstablishment($user->getEstablishment());

        if ($user->isApprentissage()) {
            $termsConditions = $user->getEstablishment()->getTermsConditionsAlternance();
        } else {
            $termsConditions = $user->getEstablishment()->getTermsConditionsPro();
        }
        
        return $this->render('terms_acceptance/index.html.twig', [
            'termsConditions' => $termsConditions,
        ]);
    }

    /**
    * Validate route
    */
 
    #[Route('/terms-acceptance/validation', name: 'app_terms_acceptance_validation')]
    public function validate(
        Request $request,
        SchoolYearRepository $SYRepo,
        TermsAcceptanceRepository $termsRepo,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $activeYear = $SYRepo->findActiveByEstablishment($user->getEstablishment());

        $existingAcceptance = $termsRepo->findOneBy([
            'user' => $user,
            'schoolYear' => $activeYear
        ]);

        if ($existingAcceptance) {
            $this->addFlash('warning', 'Vous avez déjà validé les termes pour l’année active.');
            return $this->redirectToRoute('app_home');
        }

        // English comment: Create Symfony form
        $form = $this->createForm(TermsAcceptanceType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $terms = new TermsAcceptance();
                $terms->setUser($user);
                $terms->setSchoolYear($activeYear);

                $em->persist($terms);
                $em->flush();

                $this->addFlash('success', 'Votre validation a été enregistrée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l’enregistrement.');
            }

            return $this->redirectToRoute('app_home');
        }

        if (in_array('ROLE_STUDENT', $user->getRoles(), true)) {
            if ($user->isApprentissage()) {
                $termsConditions = $user->getClassroom()->getTermsConditionsAlternance();
            } else {
                $termsConditions = $user->getClassroom()->getTermsConditionsPro();
            }
        } else {
            if ($user->isApprentissage()) {
                $termsConditions = $user->getEstablishment()->getTermsConditionsAlternance();
            } else {
                $termsConditions = $user->getEstablishment()->getTermsConditionsPro();
            }
        }
        
        return $this->render('terms_acceptance/validate.html.twig', [
            'schoolYear' => $activeYear,
            'user' => $user,
            'termsConditions' => $termsConditions,
            'form' => $form->createView()
        ]);
    }
}
