<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;



class LoginCest
{
    public function loginWithValidCredentials(AcceptanceTester $I)
    {
        $I->wantTo('Log in with valid credentials');

        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'poubellepoubelle106@gmail.com'); 
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');

        $I->waitForText('Bienvenue sur le livret', 5);

        $I->seeInCurrentUrl("/");
    }

    public function loginWithInvalidCredentials(AcceptanceTester $I)
    {
        $I->wantTo('Log in with invalid credentials');

        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'poubellepoubelle106@gmail.com');
        $I->fillField('#inputPassword', 'mauvais_motdepasse');
        $I->click('form button[type=submit]');
        
        $I->waitForText('Invalid credentials.', 5);

        $I->seeInCurrentUrl("/login");
    }


    public function loginWithDisabledAccount(AcceptanceTester $I)
    {
        $I->wantTo('Log in with a disabled account');

        $I->amOnPage('/login');
        $I->fillField('#inputEmail', 'charlie.durand@example.com');
        $I->fillField('#inputPassword', 'Password123!Oui');
        $I->click('form button[type=submit]');
       
        $I->waitForText('Connexion refusée.', 5);

        $I->seeInCurrentUrl("/login");
    }
}