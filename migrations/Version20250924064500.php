<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924064500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period DROP FOREIGN KEY FK_C5B81ECE444E1AE8');
        $this->addSql('DROP INDEX IDX_C5B81ECE444E1AE8 ON period');
        $this->addSql('ALTER TABLE period CHANGE schoolyear_id school_year_id INT NOT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECED2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id)');
        $this->addSql('CREATE INDEX IDX_C5B81ECED2EECC3F ON period (school_year_id)');
        $this->addSql('ALTER TABLE student_evaluation ADD period_id INT NOT NULL');
        $this->addSql('ALTER TABLE student_evaluation ADD CONSTRAINT FK_FEFC4894EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_FEFC4894EC8B7ADE ON student_evaluation (period_id)');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD tutor_evaluation_id INT NOT NULL, ADD behavior_criteria_id INT NOT NULL, ADD behavior_level_id INT NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD CONSTRAINT FK_1F2BBDD553769787 FOREIGN KEY (tutor_evaluation_id) REFERENCES tutor_evaluation (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD CONSTRAINT FK_1F2BBDD5BFF9146E FOREIGN KEY (behavior_criteria_id) REFERENCES behavior_criteria (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD CONSTRAINT FK_1F2BBDD5B849592C FOREIGN KEY (behavior_level_id) REFERENCES behavior_level (id)');
        $this->addSql('CREATE INDEX IDX_1F2BBDD553769787 ON tutor_evaluation_behavior (tutor_evaluation_id)');
        $this->addSql('CREATE INDEX IDX_1F2BBDD5BFF9146E ON tutor_evaluation_behavior (behavior_criteria_id)');
        $this->addSql('CREATE INDEX IDX_1F2BBDD5B849592C ON tutor_evaluation_behavior (behavior_level_id)');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD tutor_evaluation_id INT NOT NULL, ADD skill_criteria_id INT NOT NULL, ADD skill_level_id INT NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD CONSTRAINT FK_24247A9253769787 FOREIGN KEY (tutor_evaluation_id) REFERENCES tutor_evaluation (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD CONSTRAINT FK_24247A92271573D9 FOREIGN KEY (skill_criteria_id) REFERENCES skill_criteria (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD CONSTRAINT FK_24247A921D192655 FOREIGN KEY (skill_level_id) REFERENCES skill_level (id)');
        $this->addSql('CREATE INDEX IDX_24247A9253769787 ON tutor_evaluation_skill (tutor_evaluation_id)');
        $this->addSql('CREATE INDEX IDX_24247A92271573D9 ON tutor_evaluation_skill (skill_criteria_id)');
        $this->addSql('CREATE INDEX IDX_24247A921D192655 ON tutor_evaluation_skill (skill_level_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period DROP FOREIGN KEY FK_C5B81ECED2EECC3F');
        $this->addSql('DROP INDEX IDX_C5B81ECED2EECC3F ON period');
        $this->addSql('ALTER TABLE period CHANGE school_year_id schoolyear_id INT NOT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE444E1AE8 FOREIGN KEY (schoolyear_id) REFERENCES school_year (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C5B81ECE444E1AE8 ON period (schoolyear_id)');
        $this->addSql('ALTER TABLE student_evaluation DROP FOREIGN KEY FK_FEFC4894EC8B7ADE');
        $this->addSql('DROP INDEX IDX_FEFC4894EC8B7ADE ON student_evaluation');
        $this->addSql('ALTER TABLE student_evaluation DROP period_id');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP FOREIGN KEY FK_24247A9253769787');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP FOREIGN KEY FK_24247A92271573D9');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP FOREIGN KEY FK_24247A921D192655');
        $this->addSql('DROP INDEX IDX_24247A9253769787 ON tutor_evaluation_skill');
        $this->addSql('DROP INDEX IDX_24247A92271573D9 ON tutor_evaluation_skill');
        $this->addSql('DROP INDEX IDX_24247A921D192655 ON tutor_evaluation_skill');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP tutor_evaluation_id, DROP skill_criteria_id, DROP skill_level_id');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP FOREIGN KEY FK_1F2BBDD553769787');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP FOREIGN KEY FK_1F2BBDD5BFF9146E');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP FOREIGN KEY FK_1F2BBDD5B849592C');
        $this->addSql('DROP INDEX IDX_1F2BBDD553769787 ON tutor_evaluation_behavior');
        $this->addSql('DROP INDEX IDX_1F2BBDD5BFF9146E ON tutor_evaluation_behavior');
        $this->addSql('DROP INDEX IDX_1F2BBDD5B849592C ON tutor_evaluation_behavior');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP tutor_evaluation_id, DROP behavior_criteria_id, DROP behavior_level_id');
    }
}
