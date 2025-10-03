<?php

namespace App\Controller;

use App\Entity\TermsAcceptance;
use App\Repository\SchoolYearRepository;
use App\Repository\TermsAcceptanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class TermsAcceptanceController extends AbstractController
{
    /**
     * Route to see what the user valid
     */
    #[Route('/terms-acceptance', name: 'app_terms_acceptance')]
    public function index(Request $request, SchoolYearRepository $SYRepo): Response
    {
        $activeYear = $SYRepo->findActive();
        $termsContent = $activeYear->getTermsContent();

        return $this->render('terms_acceptance/index.html.twig', [
            'termsContent' => $termsContent
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
        $user = $this->getUser();
        $activeYear = $SYRepo->findActive();


        $existingAcceptance = $termsRepo->findOneBy([
            'user' => $user,
            'schoolYear' => $activeYear
        ]);

        if ($existingAcceptance) {
            $this->addFlash('warning', 'Vous avez déjà validé les termes pour l’année active.');
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
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


        $termsContent = $activeYear->getTermsContent();

        return $this->render('terms_acceptance/validate.html.twig', [
            'schoolYear' => $activeYear,
            'user' => $user,
            'termsContent' => $termsContent,
        ]);
    }
}
