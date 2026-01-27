<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class EvaluationCest
{
    public function _before(AcceptanceTester $I): void
    {
        // Common setup if needed
    }

    /**
     * Test: Student cannot self-evaluate without tutor evaluation
     *
     * Dataset: Student 14 (student.no.tutor@example.com) has NO evaluations
     * Expected: Student is redirected when trying to self-evaluate
     */
    public function testStudentCannotEvaluateWithoutTutorEvaluation(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'student.no.tutor@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/student/1'); // Period 1
        $I->wait(1); // Wait for redirection
        $I->seeInCurrentUrl('/'); // Redirected to home
        $I->waitForText('Vous ne pouvez pas encore faire', 5);
    }

    /**
     * Test: TTM cannot evaluate without both tutor and student evaluations
     *
     * Dataset: Student 17 (student.no.evals@example.com) has NO evaluations
     * Expected: TTM is redirected when trying to evaluate
     */
    public function testTtmCannotEvaluateWithoutBothEvaluations(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'ttm.test@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/ttm/17/1'); // Student 17 (no evaluations), Period 1
        $I->wait(2); // Wait for redirection and page load
        $I->seeInCurrentUrl('/'); // Redirected to home
        $I->waitForText('Le TTM ne peut pas encore', 5);
    }

    /**
     * Test: TTM cannot evaluate with only tutor evaluation (missing student evaluation)
     *
     * Dataset: Student 15 (student.with.tutor@example.com) has tutor evaluation ONLY
     * Expected: TTM is redirected when trying to evaluate
     */
    public function testTtmCannotEvaluateWithOnlyTutorEvaluation(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'ttm.test@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/ttm/15/1'); // Student 15 (tutor eval only), Period 1
        $I->wait(2); // Wait for redirection
        $I->seeInCurrentUrl('/'); // Redirected to home
        $I->waitForText('Le TTM ne peut pas encore évaluer', 5);
    }

    /**
     * Test: Complete evaluation flow - Tutor → Student → TTM
     *
     * Dataset: Student 18 (student.flow.test@example.com) has NO evaluations
     *          Tutor 12 (tutor.test@example.com) is assigned to student 18
     * Expected: Full evaluation flow succeeds (tutor → student → ttm)
     */
    public function testCompleteEvaluationFlow(AcceptanceTester $I): void
    {
        // === PHASE 1: TUTOR EVALUATION ===
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'tutor.test@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

       
        // Verify we are on the tutor evaluation page
        $I->amOnPage('/evaluation/tutor/18/1');
        $I->wait(2);
        // Protect against double submission
        if ($I->tryToSee('Une évaluation existe déjà')) {
            $I->waitForText('Une évaluation existe déjà', 10);
            $I->seeInCurrentUrl('/');
            return;
        }

        $I->see('Évaluation de l\'alternant');

        // Fill tutor evaluation form - Behavior grid (radio buttons)
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[behaviorEvaluation][0][behaviorLevel]"][value="1"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[behaviorEvaluation][1][behaviorLevel]"][value="7"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[behaviorEvaluation][2][behaviorLevel]"][value="4"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[behaviorEvaluation][3][behaviorLevel]"][value="10"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[behaviorEvaluation][4][behaviorLevel]"][value="13"]\').checked = true;');

        // Skill grid (radio buttons)
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[skillEvaluation][0][skillLevel]"][value="3"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[skillEvaluation][1][skillLevel]"][value="4"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[skillEvaluation][2][skillLevel]"][value="3"]\').checked = true;');
        $I->executeJS('document.querySelector(\'input[name="tutor_evaluation_form[skillEvaluation][3][skillLevel]"][value="4"]\').checked = true;');

        $I->fillField('tutor_evaluation_form[strengths]', 'Excellent aptitude en mathématiques et programmation.');
        $I->fillField('tutor_evaluation_form[weaknesses]', 'Doit améliorer la gestion du temps.');
        $I->fillField('tutor_evaluation_form[goals]', 'Continuer à développer les compétences analytiques.');
        $I->fillField('tutor_evaluation_form[remarks]', 'Très bon travail global, progression constante.');

        $I->scrollTo('#submit-evaluation');
        $I->wait(1);
        $I->click('#submit-evaluation');
        $I->waitForText('Évaluation enregistrée avec succès', 5);
        $I->seeInCurrentUrl('/');

        // Logout tutor
        $I->amOnPage('/logout');
        $I->seeInCurrentUrl('/login');

        // === PHASE 2: STUDENT SELF-EVALUATION ===
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'student.flow.test@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/student/1'); // Period 1
        $I->see('Mon livret de l\'alternant');

        $I->fillField('student_evaluation_form[remarks]', 'Je pense avoir fait de bons progrès. Points à améliorer : concentration et gestion du temps.');

        $I->scrollTo('#submit-evaluation');
        $I->wait(1);
        $I->click('#submit-evaluation');
        $I->waitForText('Votre auto-évaluation a été enregistrée avec succès', 5);

        $I->amOnPage('/logout');
        $I->seeInCurrentUrl('/login');

        // === PHASE 3: TTM EVALUATION ===
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'ttm.test@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->wait(1);
        $I->seeInCurrentUrl('/');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/ttm/18/1'); // Student 18, Period 1
        $I->see('Evaluer l\'alternant');

        $I->fillField('ttm_evaluation_form[remarks]', 'Évaluation complète : l\'étudiant démontre une forte motivation. Continuer dans cette direction.');

        $I->scrollTo('#submit-evaluation');
        $I->wait(1);
        $I->click('#submit-evaluation');
        $I->waitForText('Évaluation TTM enregistrée avec succès', 5);

        // === PHASE 4: VERIFICATION IN EVALUATION VISUALIZER ===
        $I->amOnPage('/evaluation-visualizer');
        $I->see('Validations des livrets');
        
        $I->scrollTo('//tr[contains(., "Student FlowTest")]');
        $I->see('Student FlowTest');

        $I->seeNumberOfElements('//tr[contains(., "Student FlowTest")]//span[@class="text-success"]', 3);
    }

    /**
     * Test: Tutor cannot re-evaluate the same student/period
     *
     * Dataset: Student 2 (alice.dupont@example.com) already has tutor evaluation (id 1)
     *          Tutor 1 (fradet.thibault23@gmail.com) created the evaluation
     * Expected: Tutor is redirected when trying to re-evaluate
     */
    public function testTutorCannotReevaluate(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'fradet.thibault23@gmail.com'); // Admin who is also a tutor
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/tutor/2/1'); // Student 2, Period 1 (already has tutor evaluation)
        $I->wait(2); // Wait for redirection and alert rendering

        $I->seeInCurrentUrl('/'); // Redirected to home
        $I->waitForText('Une évaluation existe déjà', 10);
    }

    /**
     * Test: Student cannot re-evaluate the same period
     *
     * Dataset: Student 2 (alice.dupont@example.com) already has student evaluation (id 1)
     * Expected: Student is redirected when trying to re-evaluate
     */
    public function testStudentCannotReevaluate(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'alice.dupont@example.com'); // Student with self-evaluation
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/student/1'); // Period 1 (already has self-evaluation)
        $I->wait(2); // Wait for redirection

        $I->seeInCurrentUrl('/'); // Redirected to home
        $I->see('Vous avez déjà rempli votre auto-évaluation pour cette période.');
    }

    /**
     * Test: TTM cannot re-evaluate the same student/period
     *
     * Dataset: Student 16 (student.complete@example.com) has complete evaluation chain:
     *          - Tutor evaluation (id 4)
     *          - Student evaluation (id 5)
     *          - TTM evaluation (id 1)
     * Expected: TTM is redirected when trying to re-evaluate
     */
    public function testTtmCannotReevaluate(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'ttm.test@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
        $I->wait(1); // Wait for page transition
        $I->seeInCurrentUrl('/'); // Verify we're on home page
        $I->waitForText('Bienvenue sur le livret', 5);

        $I->amOnPage('/evaluation/ttm/16/1'); // Student 16, Period 1 (already has TTM evaluation)
        $I->wait(2); // Wait for redirection and alert rendering
        $I->seeInCurrentUrl('/'); // Redirected to home
        $I->waitForText('Une évaluation TTM a déjà été réalisée', 10);
    }
}
