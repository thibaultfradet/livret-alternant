<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121082706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE behavior_criteria ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE behavior_criteria ADD CONSTRAINT FK_51E7890A8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_51E7890A8565851 ON behavior_criteria (establishment_id)');
        $this->addSql('ALTER TABLE skill_level ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill_level ADD CONSTRAINT FK_BFC25F2F8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_BFC25F2F8565851 ON skill_level (establishment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE behavior_criteria DROP FOREIGN KEY FK_51E7890A8565851');
        $this->addSql('DROP INDEX IDX_51E7890A8565851 ON behavior_criteria');
        $this->addSql('ALTER TABLE behavior_criteria DROP establishment_id');
        $this->addSql('ALTER TABLE skill_level DROP FOREIGN KEY FK_BFC25F2F8565851');
        $this->addSql('DROP INDEX IDX_BFC25F2F8565851 ON skill_level');
        $this->addSql('ALTER TABLE skill_level DROP establishment_id');
    }
}
