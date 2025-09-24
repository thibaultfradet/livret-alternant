<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924093243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom DROP planning, DROP calendar');
        $this->addSql('ALTER TABLE ttmclassroom CHANGE role label VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation ADD contents JSON NOT NULL, DROP strengths, DROP weaknesses, DROP goals, DROP remarks');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom ADD planning VARCHAR(255) NOT NULL, ADD calendar VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ttmclassroom CHANGE label role VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation ADD strengths LONGTEXT NOT NULL, ADD weaknesses LONGTEXT NOT NULL, ADD goals LONGTEXT NOT NULL, ADD remarks LONGTEXT NOT NULL, DROP contents');
    }
}
