<?php

namespace App\Controller;

use App\Entity\TermsAcceptance;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TermsAcceptanceController extends AbstractController
{
    #[Route('/terms-acceptance', name: 'app_terms_acceptance')]
    public function index(Request $request, SchoolYearRepository $SYRepo, EntityManagerInterface $em): Response
    {

        if ($request->isMethod('POST')) {
            try {
                $user = $this->getUser();
                $year = $SYRepo->findActive();

                $terms = new TermsAcceptance();
                $terms->setUser($user);
                $terms->setSchoolYear($year);

                $em->persist($terms);
                $em->flush();
            } catch (\Exception $e) {
                dump($e);
            }
            return $this->redirectToRoute('app_home');
        }

        // If GET request, maybe render the form
        return $this->render('terms_acceptance/index.html.twig');
    }
}
