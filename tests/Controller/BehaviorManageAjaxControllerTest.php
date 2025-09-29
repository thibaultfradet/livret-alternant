<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BehaviorManageAjaxControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/behavior/manage/ajax');

        self::assertResponseIsSuccessful();
    }
}
