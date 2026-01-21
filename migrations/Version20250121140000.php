<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add establishment relations to BehaviorCriteria, BehaviorLevel and SkillLevel';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE behavior_criteria ADD establishment_id INT NOT NULL');
        $this->addSql('ALTER TABLE behavior_criteria ADD CONSTRAINT FK_8B9E4E5B21856F91 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_8B9E4E5B21856F91 ON behavior_criteria (establishment_id)');

        $this->addSql('ALTER TABLE behavior_level ADD establishment_id INT NOT NULL');
        $this->addSql('ALTER TABLE behavior_level ADD CONSTRAINT FK_4B4E3E3F21856F91 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_4B4E3E3F21856F91 ON behavior_level (establishment_id)');

        $this->addSql('ALTER TABLE skill_level ADD establishment_id INT NOT NULL');
        $this->addSql('ALTER TABLE skill_level ADD CONSTRAINT FK_2F3E4A5C21856F91 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_2F3E4A5C21856F91 ON skill_level (establishment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_level DROP FOREIGN KEY FK_2F3E4A5C21856F91');
        $this->addSql('DROP INDEX IDX_2F3E4A5C21856F91 ON skill_level');
        $this->addSql('ALTER TABLE skill_level DROP establishment_id');

        $this->addSql('ALTER TABLE behavior_level DROP FOREIGN KEY FK_4B4E3E3F21856F91');
        $this->addSql('DROP INDEX IDX_4B4E3E3F21856F91 ON behavior_level');
        $this->addSql('ALTER TABLE behavior_level DROP establishment_id');

        $this->addSql('ALTER TABLE behavior_criteria DROP FOREIGN KEY FK_8B9E4E5B21856F91');
        $this->addSql('DROP INDEX IDX_8B9E4E5B21856F91 ON behavior_criteria');
        $this->addSql('ALTER TABLE behavior_criteria DROP establishment_id');
    }
}