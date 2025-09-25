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
use App\Form\TutorEvaluationFormType;
use App\Repository\BehaviorCriteriaRepository;
use App\Repository\BehaviorLevelRepository;
use App\Repository\PeriodRepository;
use App\Repository\SkillCriteriaRepository;
use App\Repository\SkillLevelRepository;
use App\Repository\UserRepository;
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
        // get && set context
        $evaluation = new TutorEvaluation();
        $evaluation->setStudent($student);
        $evaluation->setTutor($this->getUser());
        $evaluation->setPeriod($period);

        $classroom = $student->getClassroom();
        $diploma = $classroom->getDiploma();

        $skillsCriteria = $SCRepo->createQueryBuilder('sc')
            ->join('sc.skillGroup', 'sg')
            ->where('sc.diploma = :diploma')
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
        // set data based on context
        $evaluation = new StudentEvaluation();
        $student = $this->getUser();

        $evaluation->setStudent($student);
        $evaluation->setPeriod($period);

        // create form
        $form = $this->createForm(StudentEvaluationFormType::class, $evaluation);
        $form->handleRequest($request);
        dump("test");
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
}
