<?php

namespace App\Tests\Controller;

use App\Entity\Period;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PeriodControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $periodRepository;
    private string $path = '/period/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->periodRepository = $this->manager->getRepository(Period::class);

        foreach ($this->periodRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Period index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'period[periodNumber]' => 'Testing',
            'period[startDate]' => 'Testing',
            'period[endDate]' => 'Testing',
            'period[disabledAt]' => 'Testing',
            'period[schoolYear]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->periodRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Period();
        $fixture->setPeriodNumber('My Title');
        $fixture->setStartDate('My Title');
        $fixture->setEndDate('My Title');
        $fixture->setDisabledAt('My Title');
        $fixture->setSchoolYear('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Period');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Period();
        $fixture->setPeriodNumber('Value');
        $fixture->setStartDate('Value');
        $fixture->setEndDate('Value');
        $fixture->setDisabledAt('Value');
        $fixture->setSchoolYear('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'period[periodNumber]' => 'Something New',
            'period[startDate]' => 'Something New',
            'period[endDate]' => 'Something New',
            'period[disabledAt]' => 'Something New',
            'period[schoolYear]' => 'Something New',
        ]);

        self::assertResponseRedirects('/period/');

        $fixture = $this->periodRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getPeriodNumber());
        self::assertSame('Something New', $fixture[0]->getStartDate());
        self::assertSame('Something New', $fixture[0]->getEndDate());
        self::assertSame('Something New', $fixture[0]->getDisabledAt());
        self::assertSame('Something New', $fixture[0]->getSchoolYear());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Period();
        $fixture->setPeriodNumber('Value');
        $fixture->setStartDate('Value');
        $fixture->setEndDate('Value');
        $fixture->setDisabledAt('Value');
        $fixture->setSchoolYear('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/period/');
        self::assertSame(0, $this->periodRepository->count([]));
    }
}
