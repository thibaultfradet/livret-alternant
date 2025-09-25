<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925103709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_criteria DROP FOREIGN KEY FK_F4B7F673A99ACEB5');
        $this->addSql('DROP INDEX IDX_F4B7F673A99ACEB5 ON skill_criteria');
        $this->addSql('ALTER TABLE skill_criteria DROP diploma_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_criteria ADD diploma_id INT NOT NULL');
        $this->addSql('ALTER TABLE skill_criteria ADD CONSTRAINT FK_F4B7F673A99ACEB5 FOREIGN KEY (diploma_id) REFERENCES diploma (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F4B7F673A99ACEB5 ON skill_criteria (diploma_id)');
    }
}
