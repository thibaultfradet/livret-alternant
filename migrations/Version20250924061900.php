<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924061900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE behavior_level ADD behavior_criteria_id INT NOT NULL');
        $this->addSql('ALTER TABLE behavior_level ADD CONSTRAINT FK_C86B3819BFF9146E FOREIGN KEY (behavior_criteria_id) REFERENCES behavior_criteria (id)');
        $this->addSql('CREATE INDEX IDX_C86B3819BFF9146E ON behavior_level (behavior_criteria_id)');
        $this->addSql('ALTER TABLE skill_criteria ADD skill_group_id INT NOT NULL');
        $this->addSql('ALTER TABLE skill_criteria ADD CONSTRAINT FK_F4B7F673BCFCB4B5 FOREIGN KEY (skill_group_id) REFERENCES skill_group (id)');
        $this->addSql('CREATE INDEX IDX_F4B7F673BCFCB4B5 ON skill_criteria (skill_group_id)');
        $this->addSql('ALTER TABLE ttmevaluation ADD period_id INT NOT NULL');
        $this->addSql('ALTER TABLE ttmevaluation ADD CONSTRAINT FK_399CFC27EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_399CFC27EC8B7ADE ON ttmevaluation (period_id)');
        $this->addSql('ALTER TABLE tutor_evaluation ADD period_id INT NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation ADD CONSTRAINT FK_DBE91853EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_DBE91853EC8B7ADE ON tutor_evaluation (period_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tutor_evaluation DROP FOREIGN KEY FK_DBE91853EC8B7ADE');
        $this->addSql('DROP INDEX IDX_DBE91853EC8B7ADE ON tutor_evaluation');
        $this->addSql('ALTER TABLE tutor_evaluation DROP period_id');
        $this->addSql('ALTER TABLE ttmevaluation DROP FOREIGN KEY FK_399CFC27EC8B7ADE');
        $this->addSql('DROP INDEX IDX_399CFC27EC8B7ADE ON ttmevaluation');
        $this->addSql('ALTER TABLE ttmevaluation DROP period_id');
        $this->addSql('ALTER TABLE skill_criteria DROP FOREIGN KEY FK_F4B7F673BCFCB4B5');
        $this->addSql('DROP INDEX IDX_F4B7F673BCFCB4B5 ON skill_criteria');
        $this->addSql('ALTER TABLE skill_criteria DROP skill_group_id');
        $this->addSql('ALTER TABLE behavior_level DROP FOREIGN KEY FK_C86B3819BFF9146E');
        $this->addSql('DROP INDEX IDX_C86B3819BFF9146E ON behavior_level');
        $this->addSql('ALTER TABLE behavior_level DROP behavior_criteria_id');
    }
}
