<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EvaluationListControllerTest extends WebTestCase
{
    

    public function testTutorList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/evaluations/tutor');

        self::assertResponseIsSuccessful();
    }

    public function testTtmList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/evaluations/ttm');

        self::assertResponseIsSuccessful();
    }

    public function testStudentList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/evaluations/student');

        self::assertResponseIsSuccessful();
    }
}
