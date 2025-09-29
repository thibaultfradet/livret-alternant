<?php

namespace App\Controller;

use App\Entity\Period;
use App\Entity\StudentEvaluation;
use App\Entity\TTMEvaluation;
use App\Entity\TutorEvaluation;
use App\Entity\TutorEvaluationBehavior;
use App\Entity\TutorEvaluationSkill;
use App\Entity\User;
use App\Form\StudentEvaluationFormType;
use App\Form\TTMEvaluationFormType;
use App\Form\TutorEvaluationFormType;
use App\Repository\BehaviorCriteriaRepository;
use App\Repository\SkillCriteriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationController extends AbstractController
{


    #[Route('/evaluation/tutor/{student}/{period}', name: 'app_create_evaluation')]
    public function tutor(
        User $student,
        Period $period,
        Request $request,
        EntityManagerInterface $em,
        SkillCriteriaRepository $SCRepo,
        BehaviorCriteriaRepository $BCRepo
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_TUTOR');
        $tutor = $this->getUser();

        // verify if there is no already evaluation on period, tutor && student
        $existingEvaluation = $em->getRepository(TutorEvaluation::class)->findOneBy([
            'student' => $student,
            'tutor'   => $tutor,
            'period'  => $period,
        ]);

        if ($existingEvaluation) {
            $this->addFlash('warning', 'Une évaluation existe déjà pour cet étudiant et cette période.');
            return $this->redirectToRoute('app_home');
        }


        // get && set context
        $evaluation = new TutorEvaluation();
        $evaluation->setStudent($student);
        $evaluation->setTutor($this->getUser());
        $evaluation->setPeriod($period);

        $classroom = $student->getClassroom();
        $diploma = $classroom->getDiploma();

        $skillsCriteria = $SCRepo->createQueryBuilder('sc')
            ->join('sc.skillGroup', 'sg')
            ->where('sg.diploma = :diploma')
            ->andWhere('sc.disabledAt IS NULL')
            ->andWhere('sg.disabledAt IS NULL')
            ->setParameter('diploma', $diploma)
            ->orderBy('sg.label', 'ASC')
            ->addOrderBy('sc.label', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($skillsCriteria as $criteria) {
            $skill = new TutorEvaluationSkill();
            $skill->setSkillCriteria($criteria);
            $evaluation->addSkillEvaluation($skill);
        }

        $behaviorCriteriaList = $BCRepo->createQueryBuilder('bc')
            ->where('bc.disabledAt IS NULL')
            ->orderBy('bc.label', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($behaviorCriteriaList as $criteria) {
            $behavior = new TutorEvaluationBehavior();
            $behavior->setBehaviorCriteria($criteria);

            $levels = $criteria->getBehaviorLevels()->filter(fn($lvl) => $lvl->getDisabledAt() === null);

            $behavior->setAvailableLevels($levels);
            $evaluation->addBehaviorEvaluation($behavior);
        }


        // create form data
        $form = $this->createForm(TutorEvaluationFormType::class, $evaluation);
        $form->handleRequest($request);

        // handle submit && create data
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($evaluation);
            $em->flush();

            $this->addFlash('success', 'Évaluation enregistrée avec succès !');

            return $this->redirectToRoute('app_home');
        }



        return $this->render('evaluation/tutor.html.twig', [
            'form' => $form->createView(),
            'student' => $student,
            'period' => $period,
            'skillsCriteria' => $skillsCriteria,
            'behaviorCriteriaList' => $behaviorCriteriaList,
        ]);
    }


    #[Route('/evaluation/student/{period}', name: 'app_create_student_evaluation')]
    public function student(
        Period $period,
        Request $request,
        EntityManagerInterface $em,
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $student = $this->getUser();
        // required tutor evaluation
        $alreadyTutorEvaluation = $em->getRepository(TutorEvaluation::class)->findOneBy([
            'student' => $student,
            'period' => $period,
        ]);

        if (!$alreadyTutorEvaluation) {
            $this->addFlash('warning', 'Vous ne pouvez pas encore faire votre auto-évaluation, votre tuteur doit d’abord évaluer votre période.');
            return $this->redirectToRoute('app_home');
        }

        // check for the existence of an evaluation in the same period
        $existingEvaluation = $em->getRepository(StudentEvaluation::class)->findOneBy([
            'student' => $student,
            'period'  => $period,
        ]);

        if ($existingEvaluation) {
            $this->addFlash('warning', 'Vous avez déjà rempli votre auto-évaluation pour cette période.');
            return $this->redirectToRoute('app_home');
        }

        // set data based on context
        $evaluation = new StudentEvaluation();

        $evaluation->setStudent($student);
        $evaluation->setPeriod($period);

        // create form
        $form = $this->createForm(StudentEvaluationFormType::class, $evaluation);
        $form->handleRequest($request);

        // handle submit
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($evaluation);
            $em->flush();

            $this->addFlash('success', 'Votre auto-évaluation a été enregistrée avec succès !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('evaluation/student.html.twig', [
            'form' => $form->createView(),
            'student' => $student,
            'period' => $period,
        ]);
    }



    #[Route('/evaluation/ttm/{student}/{period}', name: 'app_create_ttm_evaluation')]
    public function ttm(
        User $student,
        Period $period,
        Request $request,
        EntityManagerInterface $em,
        Security $security
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_TTM');

        // check for existing evaluation
        $existingEvaluation = $em->getRepository(TTMEvaluation::class)->findOneBy([
            'student' => $student,
            'period'  => $period,
        ]);

        if ($existingEvaluation) {
            $this->addFlash('warning', 'Une évaluation TTM a déjà été réalisée pour cet étudiant et cette période par un membre de l’équipe pédagogique.');
            return $this->redirectToRoute('app_home');
        }

        // required tutor && student evaluation
        $tutorEvaluation = $em->getRepository(TutorEvaluation::class)->findOneBy([
            'student' => $student,
            'period' => $period,
        ]);

        $studentEvaluation = $em->getRepository(StudentEvaluation::class)->findOneBy([
            'student' => $student,
            'period' => $period,
        ]);

        if (!$tutorEvaluation || !$studentEvaluation) {
            $this->addFlash('warning', 'Le TTM ne peut pas encore évaluer cet étudiant car le tuteur et/ou l’étudiant n’ont pas encore rempli leurs évaluations.');
            return $this->redirectToRoute('app_home');
        }



        // fill object using context
        $evaluation = new TTMEvaluation();
        $ttm = $security->getUser();

        $evaluation->setStudent($student);
        $evaluation->setTtm($ttm);
        $evaluation->setPeriod($period);


        // create form using the form type
        $form = $this->createForm(TTMEvaluationFormType::class, $evaluation);
        $form->handleRequest($request);

        // handle submit
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($evaluation);
            $em->flush();

            $this->addFlash('success', 'Évaluation TTM enregistrée avec succès !');

            return $this->redirectToRoute('app_home');
        }

        // Rend la même vue Twig que l'évaluation étudiant
        return $this->render('evaluation/ttm.html.twig', [
            'form' => $form->createView(),
            'student' => $student,
            'period' => $period,
            'button_label' => 'Valider mon évaluation TTM'
        ]);
    }
}
