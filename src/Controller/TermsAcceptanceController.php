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
            $termsConditions = $user->getEstablishment()->getTermsConditionsApprentissage();
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
 
    #[Route(‘/terms-acceptance/validation’, name: ‘app_terms_acceptance_validation’)]
    public function validate(
        Request $request,
        SchoolYearRepository $SYRepo,
        TermsAcceptanceRepository $termsRepo,
        EntityManagerInterface $em
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $isTutor = $user->getEstablishment() === null;
        $activeYear = null;
        $establishment = null;

        if ($isTutor) {
            $seenYearIds = [];
            foreach ($user->getTutorContracts() as $contract) {
                $schoolYear = $contract->getStudent()?->getClassroom()?->getSchoolYear();
                if (!$schoolYear || !$schoolYear->isActive()) {
                    continue;
                }
                if (\in_array($schoolYear->getId(), $seenYearIds, true)) {
                    continue;
                }
                $seenYearIds[] = $schoolYear->getId();

                $existing = $termsRepo->findOneBy([‘user’ => $user, ‘schoolYear’ => $schoolYear]);
                if (!$existing) {
                    $activeYear = $schoolYear;
                    $establishment = $schoolYear->getEstablishment();
                    break;
                }
            }

            if (!$activeYear) {
                return $this->redirectToRoute(‘app_home’);
            }
        } else {
            $establishment = $user->getEstablishment();
            $activeYear = $SYRepo->findActiveByEstablishment($establishment);

            $existingAcceptance = $termsRepo->findOneBy([
                ‘user’ => $user,
                ‘schoolYear’ => $activeYear
            ]);

            if ($existingAcceptance) {
                $this->addFlash(‘warning’, "Vous avez déjà validé les termes pour l’année active.");
                return $this->redirectToRoute(‘app_home’);
            }
        }

        $form = $this->createForm(TermsAcceptanceType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $terms = new TermsAcceptance();
                $terms->setUser($user);
                $terms->setSchoolYear($activeYear);

                $em->persist($terms);
                $em->flush();

                $this->addFlash(‘success’, ‘Votre validation a été enregistrée avec succès.’);
            } catch (\Exception $e) {
                $this->addFlash(‘danger’, ‘Une erreur est survenue lors de l\’enregistrement.’);
            }

            // Tutor: redirect back if more school years still need acceptance
            if ($isTutor) {
                $seenYearIds = [];
                foreach ($user->getTutorContracts() as $contract) {
                    $schoolYear = $contract->getStudent()?->getClassroom()?->getSchoolYear();
                    if (!$schoolYear || !$schoolYear->isActive()) {
                        continue;
                    }
                    if (\in_array($schoolYear->getId(), $seenYearIds, true)) {
                        continue;
                    }
                    $seenYearIds[] = $schoolYear->getId();

                    $existing = $termsRepo->findOneBy([‘user’ => $user, ‘schoolYear’ => $schoolYear]);
                    if (!$existing) {
                        return $this->redirectToRoute(‘app_terms_acceptance_validation’);
                    }
                }
            }

            return $this->redirectToRoute(‘app_home’);
        }

        if (\in_array(‘ROLE_STUDENT’, $user->getRoles(), true)) {
            $termsConditions = $user->isApprentissage()
                ? $user->getClassroom()->getTermsConditionsApprentissage()
                : $user->getClassroom()->getTermsConditionsPro();
        } else {
            $termsConditions = $user->isApprentissage()
                ? $establishment->getTermsConditionsApprentissage()
                : $establishment->getTermsConditionsPro();
        }

        return $this->render(‘terms_acceptance/validate.html.twig’, [
            ‘schoolYear’ => $activeYear,
            ‘user’ => $user,
            ‘termsConditions’ => $termsConditions,
            ‘form’ => $form->createView()
        ]);
    }
}
