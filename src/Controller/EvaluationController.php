<?php

namespace App\Controller;

use App\Entity\Period;
use App\Entity\StudentEvaluation;
use App\Entity\TTMEvaluation;
use App\Entity\TutorEvaluation;
use App\Entity\TutorEvaluationBehavior;
use App\Entity\TutorEvaluationSkill;
use App\Entity\User;
use App\Form\TutorEvaluationFormType;
use App\Repository\BehaviorCriteriaRepository;
use App\Repository\BehaviorLevelRepository;
use App\Repository\PeriodRepository;
use App\Repository\SkillCriteriaRepository;
use App\Repository\SkillLevelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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




    // #[Route('/evaluation/tuteur/{id}', name: 'app_evaluation_tuteur')]
    // public function tuteur(int $id, Request $request, UserRepository $userRepo, SkillCriteriaRepository $SCRepo, SkillLevelRepository $SLRepo, BehaviorCriteriaRepository $BCRepo, BehaviorLevelRepository $BLRepo): Response
    // {

    //     $this->denyAccessUnlessGranted('ROLE_TUTOR');


    //     if ($request->isMethod('POST')) {
    //     }

    //     //get all skills and behavior and level
    //     //warning : we only want the attached skill of the class level

    //     $user = $this->getUser();

    //     $critere_competence = $SCRepo->createQueryBuilder('c')
    //         ->join('c.titresCompetence', 't')
    //         ->join('t.classe', 'cl')
    //         ->where('cl = :classe')
    //         ->setParameter('classe', $user->getClassroom())
    //         ->getQuery()
    //         ->getResult();


    //     $critere_comportement = $BCRepo->findAll();
    //     $niveau_comportement =  $BLRepo->findAll();

    //     // get the concerned student
    //     $student = $userRepo->findOneBy(['id' => $id]);

    //     return $this->render('evaluation/tuteur.html.twig', [
    //         'controller_name' => 'evaluationController',
    //         'critere_competence' => $critere_competence,
    //         'critere_comportement' => $critere_comportement,
    //         'niveau_comportement' => $niveau_comportement,
    //         'student' => $student,
    //     ]);
    // }


    #[Route('/evaluation/student', name: 'app_evaluation_student')]
    public function student(Request $request, PeriodRepository $periodRepo, EntityManagerInterface $em): Response
    {

        $this->denyAccessUnlessGranted('ROLE_STUDENT');

        if ($request->isMethod('POST')) {

            $user = $this->getUser();

            //can be replace with a selector on periode on the before page? 
            $now = date('Y-m-d');
            $periode = $periodRepo->createQueryBuilder('p')
                ->where('p.id_classe = :classe')
                ->andWhere('p.date_debut <= :now')
                ->andWhere('p.date_fin >= :now')
                ->setParameter('classe', $user->getClasse()->getId())
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->getOneOrNullResult();


            // get data and create item
            $remarque = $request->query->get('remarque');

            $user = $this->getUser();
            $now = new \DateTime();

            $evaluation = new StudentEvaluation();
            $evaluation->setPeriod($periode);
            $evaluation->setStudent($user);
            $evaluation->setRemarks($remarque);


            // Persister et flush
            $em->persist($evaluation);
            $em->flush();
        }

        return $this->render('evaluation/student.html.twig', [
            'controller_name' => 'evaluationController',
        ]);
    }

    #[Route('/evaluation/ttm/{id}', name: 'app_evaluation_ttm')]
    public function ttm(int $id, Request $request, UserRepository $userRepo, PeriodRepository $periodeRepo, EntityManagerInterface $em): Response
    {
        // only ttm
        $this->denyAccessUnlessGranted('ROLE_TTM');

        if ($request->isMethod('POST')) {

            $user = $this->getUser();

            $now = date('Y-m-d');
            $periode = $periodeRepo->createQueryBuilder('p')
                ->where('p.id_classe = :classe')
                ->andWhere('p.date_debut <= :now')
                ->andWhere('p.date_fin >= :now')
                ->setParameter('classe', $user->getClasse()->getId())
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->getOneOrNullResult();


            $student = $userRepo->findOneBy(['id' => $request->query->get('student')]);


            // get data and create item
            $remarque = $request->query->get('remarque');

            $now = new \DateTime();

            $evaluation = new TTMEvaluation();
            $evaluation->setPeriod($periode);
            $evaluation->setStudent($student);
            $evaluation->setRemarks($remarque);


            // Persister et flush
            $em->persist($evaluation);
            $em->flush();
        }

        $student = $userRepo->findOneBy(['id' => $id]);

        return $this->render('evaluation/ttm.html.twig', [
            'controller_name' => 'evaluationController',
            'student' => $student,
        ]);
    }
}
