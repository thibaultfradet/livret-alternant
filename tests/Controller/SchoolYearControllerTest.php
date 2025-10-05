<?php

namespace App\Tests\Controller;

use App\Entity\SchoolYear;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SchoolYearControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $schoolYearRepository;
    private string $path = '/school-year/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->schoolYearRepository = $this->manager->getRepository(SchoolYear::class);

        foreach ($this->schoolYearRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('SchoolYear index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'school_year[label]' => 'Testing',
            'school_year[startDate]' => 'Testing',
            'school_year[endDate]' => 'Testing',
            'school_year[active]' => 'Testing',
            'school_year[termsContent]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->schoolYearRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new SchoolYear();
        $fixture->setLabel('My Title');
        $fixture->setStartDate('My Title');
        $fixture->setEndDate('My Title');
        $fixture->setActive('My Title');
        $fixture->setTermsContent('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('SchoolYear');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new SchoolYear();
        $fixture->setLabel('Value');
        $fixture->setStartDate('Value');
        $fixture->setEndDate('Value');
        $fixture->setActive('Value');
        $fixture->setTermsContent('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'school_year[label]' => 'Something New',
            'school_year[startDate]' => 'Something New',
            'school_year[endDate]' => 'Something New',
            'school_year[active]' => 'Something New',
            'school_year[termsContent]' => 'Something New',
        ]);

        self::assertResponseRedirects('/school-year/');

        $fixture = $this->schoolYearRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getLabel());
        self::assertSame('Something New', $fixture[0]->getStartDate());
        self::assertSame('Something New', $fixture[0]->getEndDate());
        self::assertSame('Something New', $fixture[0]->getActive());
        self::assertSame('Something New', $fixture[0]->getTermsContent());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new SchoolYear();
        $fixture->setLabel('Value');
        $fixture->setStartDate('Value');
        $fixture->setEndDate('Value');
        $fixture->setActive('Value');
        $fixture->setTermsContent('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/school-year/');
        self::assertSame(0, $this->schoolYearRepository->count([]));
    }
}
