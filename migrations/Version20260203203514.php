<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203203514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE establishment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, terms_conditions_pro LONGTEXT DEFAULT NULL, terms_conditions_apprentissage LONGTEXT DEFAULT NULL, formation_center JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE ttmclassroom');
        $this->addSql('ALTER TABLE behavior_criteria ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE behavior_criteria ADD CONSTRAINT FK_51E7890A8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_51E7890A8565851 ON behavior_criteria (establishment_id)');
        $this->addSql('ALTER TABLE behavior_level ADD behavior_criteria_id INT NOT NULL');
        $this->addSql('ALTER TABLE behavior_level ADD CONSTRAINT FK_C86B3819BFF9146E FOREIGN KEY (behavior_criteria_id) REFERENCES behavior_criteria (id)');
        $this->addSql('CREATE INDEX IDX_C86B3819BFF9146E ON behavior_level (behavior_criteria_id)');
        $this->addSql('ALTER TABLE classroom ADD principal_teacher_id INT DEFAULT NULL, ADD terms_conditions_pro LONGTEXT DEFAULT NULL, ADD terms_conditions_apprentissage LONGTEXT DEFAULT NULL, ADD formation_center JSON DEFAULT NULL, DROP planning, DROP calendar');
        $this->addSql('ALTER TABLE classroom ADD CONSTRAINT FK_497D309D98BFDD30 FOREIGN KEY (principal_teacher_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_497D309D98BFDD30 ON classroom (principal_teacher_id)');
        $this->addSql('ALTER TABLE diploma ADD establishment_id INT NOT NULL, ADD code VARCHAR(255) NOT NULL, ADD disabled_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE diploma ADD CONSTRAINT FK_EC2189578565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_EC2189578565851 ON diploma (establishment_id)');
        $this->addSql('DROP INDEX IDX_C5B81ECE444E1AE8 ON period');
        $this->addSql('ALTER TABLE period CHANGE schoolyear_id school_year_id INT NOT NULL');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECED2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id)');
        $this->addSql('CREATE INDEX IDX_C5B81ECED2EECC3F ON period (school_year_id)');
        $this->addSql('ALTER TABLE school_year ADD establishment_id INT NOT NULL, ADD disabled_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE school_year ADD CONSTRAINT FK_FAAAACDA8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_FAAAACDA8565851 ON school_year (establishment_id)');
        $this->addSql('DROP INDEX IDX_F4B7F673A99ACEB5 ON skill_criteria');
        $this->addSql('ALTER TABLE skill_criteria CHANGE diploma_id skill_group_id INT NOT NULL');
        $this->addSql('ALTER TABLE skill_criteria ADD CONSTRAINT FK_F4B7F673BCFCB4B5 FOREIGN KEY (skill_group_id) REFERENCES skill_group (id)');
        $this->addSql('CREATE INDEX IDX_F4B7F673BCFCB4B5 ON skill_criteria (skill_group_id)');
        $this->addSql('ALTER TABLE skill_group ADD diploma_id INT NOT NULL');
        $this->addSql('ALTER TABLE skill_group ADD CONSTRAINT FK_48E8D7F9A99ACEB5 FOREIGN KEY (diploma_id) REFERENCES diploma (id)');
        $this->addSql('CREATE INDEX IDX_48E8D7F9A99ACEB5 ON skill_group (diploma_id)');
        $this->addSql('ALTER TABLE skill_level ADD establishment_id INT DEFAULT NULL, ADD color VARCHAR(15) NOT NULL, ADD order_index INT NOT NULL');
        $this->addSql('ALTER TABLE skill_level ADD CONSTRAINT FK_BFC25F2F8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_BFC25F2F8565851 ON skill_level (establishment_id)');
        $this->addSql('ALTER TABLE student_evaluation ADD period_id INT NOT NULL, ADD contents JSON NOT NULL, DROP remarks');
        $this->addSql('ALTER TABLE student_evaluation ADD CONSTRAINT FK_FEFC4894CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE student_evaluation ADD CONSTRAINT FK_FEFC4894EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_FEFC4894EC8B7ADE ON student_evaluation (period_id)');
        $this->addSql('ALTER TABLE terms_acceptance ADD CONSTRAINT FK_5937E89A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE terms_acceptance ADD CONSTRAINT FK_5937E89D2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id)');
        $this->addSql('ALTER TABLE ttmevaluation ADD period_id INT NOT NULL, ADD contents JSON NOT NULL, DROP remarks');
        $this->addSql('ALTER TABLE ttmevaluation ADD CONSTRAINT FK_399CFC277D6F9B87 FOREIGN KEY (ttm_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ttmevaluation ADD CONSTRAINT FK_399CFC27CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ttmevaluation ADD CONSTRAINT FK_399CFC27EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_399CFC27EC8B7ADE ON ttmevaluation (period_id)');
        $this->addSql('ALTER TABLE tutor_evaluation ADD period_id INT NOT NULL, ADD contents JSON NOT NULL, DROP strengths, DROP weaknesses, DROP goals, DROP remarks');
        $this->addSql('ALTER TABLE tutor_evaluation ADD CONSTRAINT FK_DBE91853208F64F1 FOREIGN KEY (tutor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_evaluation ADD CONSTRAINT FK_DBE91853CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_evaluation ADD CONSTRAINT FK_DBE91853EC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_DBE91853EC8B7ADE ON tutor_evaluation (period_id)');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD tutor_evaluation_id INT DEFAULT NULL, ADD behavior_criteria_id INT NOT NULL, ADD behavior_level_id INT NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD CONSTRAINT FK_1F2BBDD553769787 FOREIGN KEY (tutor_evaluation_id) REFERENCES tutor_evaluation (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD CONSTRAINT FK_1F2BBDD5BFF9146E FOREIGN KEY (behavior_criteria_id) REFERENCES behavior_criteria (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior ADD CONSTRAINT FK_1F2BBDD5B849592C FOREIGN KEY (behavior_level_id) REFERENCES behavior_level (id)');
        $this->addSql('CREATE INDEX IDX_1F2BBDD553769787 ON tutor_evaluation_behavior (tutor_evaluation_id)');
        $this->addSql('CREATE INDEX IDX_1F2BBDD5BFF9146E ON tutor_evaluation_behavior (behavior_criteria_id)');
        $this->addSql('CREATE INDEX IDX_1F2BBDD5B849592C ON tutor_evaluation_behavior (behavior_level_id)');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD tutor_evaluation_id INT DEFAULT NULL, ADD skill_criteria_id INT NOT NULL, ADD skill_level_id INT NOT NULL');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD CONSTRAINT FK_24247A9253769787 FOREIGN KEY (tutor_evaluation_id) REFERENCES tutor_evaluation (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD CONSTRAINT FK_24247A92271573D9 FOREIGN KEY (skill_criteria_id) REFERENCES skill_criteria (id)');
        $this->addSql('ALTER TABLE tutor_evaluation_skill ADD CONSTRAINT FK_24247A921D192655 FOREIGN KEY (skill_level_id) REFERENCES skill_level (id)');
        $this->addSql('CREATE INDEX IDX_24247A9253769787 ON tutor_evaluation_skill (tutor_evaluation_id)');
        $this->addSql('CREATE INDEX IDX_24247A92271573D9 ON tutor_evaluation_skill (skill_criteria_id)');
        $this->addSql('CREATE INDEX IDX_24247A921D192655 ON tutor_evaluation_skill (skill_level_id)');
        $this->addSql('ALTER TABLE tutor_student ADD CONSTRAINT FK_DFDBA28C208F64F1 FOREIGN KEY (tutor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_student ADD CONSTRAINT FK_DFDBA28CCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD establishment_id INT DEFAULT NULL, ADD company JSON DEFAULT NULL, ADD phone VARCHAR(255) DEFAULT NULL, ADD is_apprentissage TINYINT(1) NOT NULL, DROP address');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496278D5A8 FOREIGN KEY (classroom_id) REFERENCES classroom (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6498565851 ON user (establishment_id)');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE behavior_criteria DROP FOREIGN KEY FK_51E7890A8565851');
        $this->addSql('ALTER TABLE diploma DROP FOREIGN KEY FK_EC2189578565851');
        $this->addSql('ALTER TABLE school_year DROP FOREIGN KEY FK_FAAAACDA8565851');
        $this->addSql('ALTER TABLE skill_level DROP FOREIGN KEY FK_BFC25F2F8565851');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6498565851');
        $this->addSql('CREATE TABLE ttmclassroom (id INT AUTO_INCREMENT NOT NULL, ttm_id INT DEFAULT NULL, classroom_id INT DEFAULT NULL, role VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_990FF6667D6F9B87 (ttm_id), INDEX IDX_990FF6666278D5A8 (classroom_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE establishment');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('ALTER TABLE ttmevaluation DROP FOREIGN KEY FK_399CFC277D6F9B87');
        $this->addSql('ALTER TABLE ttmevaluation DROP FOREIGN KEY FK_399CFC27CB944F1A');
        $this->addSql('ALTER TABLE ttmevaluation DROP FOREIGN KEY FK_399CFC27EC8B7ADE');
        $this->addSql('DROP INDEX IDX_399CFC27EC8B7ADE ON ttmevaluation');
        $this->addSql('ALTER TABLE ttmevaluation ADD remarks LONGTEXT NOT NULL, DROP period_id, DROP contents');
        $this->addSql('DROP INDEX IDX_FAAAACDA8565851 ON school_year');
        $this->addSql('ALTER TABLE school_year DROP establishment_id, DROP disabled_at');
        $this->addSql('ALTER TABLE skill_criteria DROP FOREIGN KEY FK_F4B7F673BCFCB4B5');
        $this->addSql('DROP INDEX IDX_F4B7F673BCFCB4B5 ON skill_criteria');
        $this->addSql('ALTER TABLE skill_criteria CHANGE skill_group_id diploma_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_F4B7F673A99ACEB5 ON skill_criteria (diploma_id)');
        $this->addSql('ALTER TABLE skill_group DROP FOREIGN KEY FK_48E8D7F9A99ACEB5');
        $this->addSql('DROP INDEX IDX_48E8D7F9A99ACEB5 ON skill_group');
        $this->addSql('ALTER TABLE skill_group DROP diploma_id');
        $this->addSql('DROP INDEX IDX_BFC25F2F8565851 ON skill_level');
        $this->addSql('ALTER TABLE skill_level DROP establishment_id, DROP color, DROP order_index');
        $this->addSql('ALTER TABLE period DROP FOREIGN KEY FK_C5B81ECED2EECC3F');
        $this->addSql('DROP INDEX IDX_C5B81ECED2EECC3F ON period');
        $this->addSql('ALTER TABLE period CHANGE school_year_id schoolyear_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_C5B81ECE444E1AE8 ON period (schoolyear_id)');
        $this->addSql('ALTER TABLE student_evaluation DROP FOREIGN KEY FK_FEFC4894CB944F1A');
        $this->addSql('ALTER TABLE student_evaluation DROP FOREIGN KEY FK_FEFC4894EC8B7ADE');
        $this->addSql('DROP INDEX IDX_FEFC4894EC8B7ADE ON student_evaluation');
        $this->addSql('ALTER TABLE student_evaluation ADD remarks LONGTEXT NOT NULL, DROP period_id, DROP contents');
        $this->addSql('ALTER TABLE terms_acceptance DROP FOREIGN KEY FK_5937E89A76ED395');
        $this->addSql('ALTER TABLE terms_acceptance DROP FOREIGN KEY FK_5937E89D2EECC3F');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('DROP INDEX IDX_EC2189578565851 ON diploma');
        $this->addSql('ALTER TABLE diploma DROP establishment_id, DROP code, DROP disabled_at');
        $this->addSql('DROP INDEX IDX_51E7890A8565851 ON behavior_criteria');
        $this->addSql('ALTER TABLE behavior_criteria DROP establishment_id');
        $this->addSql('ALTER TABLE behavior_level DROP FOREIGN KEY FK_C86B3819BFF9146E');
        $this->addSql('DROP INDEX IDX_C86B3819BFF9146E ON behavior_level');
        $this->addSql('ALTER TABLE behavior_level DROP behavior_criteria_id');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP FOREIGN KEY FK_1F2BBDD553769787');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP FOREIGN KEY FK_1F2BBDD5BFF9146E');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP FOREIGN KEY FK_1F2BBDD5B849592C');
        $this->addSql('DROP INDEX IDX_1F2BBDD553769787 ON tutor_evaluation_behavior');
        $this->addSql('DROP INDEX IDX_1F2BBDD5BFF9146E ON tutor_evaluation_behavior');
        $this->addSql('DROP INDEX IDX_1F2BBDD5B849592C ON tutor_evaluation_behavior');
        $this->addSql('ALTER TABLE tutor_evaluation_behavior DROP tutor_evaluation_id, DROP behavior_criteria_id, DROP behavior_level_id');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP FOREIGN KEY FK_24247A9253769787');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP FOREIGN KEY FK_24247A92271573D9');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP FOREIGN KEY FK_24247A921D192655');
        $this->addSql('DROP INDEX IDX_24247A9253769787 ON tutor_evaluation_skill');
        $this->addSql('DROP INDEX IDX_24247A92271573D9 ON tutor_evaluation_skill');
        $this->addSql('DROP INDEX IDX_24247A921D192655 ON tutor_evaluation_skill');
        $this->addSql('ALTER TABLE tutor_evaluation_skill DROP tutor_evaluation_id, DROP skill_criteria_id, DROP skill_level_id');
        $this->addSql('ALTER TABLE classroom DROP FOREIGN KEY FK_497D309D98BFDD30');
        $this->addSql('DROP INDEX IDX_497D309D98BFDD30 ON classroom');
        $this->addSql('ALTER TABLE classroom ADD planning VARCHAR(255) NOT NULL, ADD calendar VARCHAR(255) NOT NULL, DROP principal_teacher_id, DROP terms_conditions_pro, DROP terms_conditions_apprentissage, DROP formation_center');
        $this->addSql('ALTER TABLE tutor_evaluation DROP FOREIGN KEY FK_DBE91853208F64F1');
        $this->addSql('ALTER TABLE tutor_evaluation DROP FOREIGN KEY FK_DBE91853CB944F1A');
        $this->addSql('ALTER TABLE tutor_evaluation DROP FOREIGN KEY FK_DBE91853EC8B7ADE');
        $this->addSql('DROP INDEX IDX_DBE91853EC8B7ADE ON tutor_evaluation');
        $this->addSql('ALTER TABLE tutor_evaluation ADD strengths LONGTEXT NOT NULL, ADD weaknesses LONGTEXT NOT NULL, ADD goals LONGTEXT NOT NULL, ADD remarks LONGTEXT NOT NULL, DROP period_id, DROP contents');
        $this->addSql('ALTER TABLE tutor_student DROP FOREIGN KEY FK_DFDBA28C208F64F1');
        $this->addSql('ALTER TABLE tutor_student DROP FOREIGN KEY FK_DFDBA28CCB944F1A');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496278D5A8');
        $this->addSql('DROP INDEX IDX_8D93D6498565851 ON user');
        $this->addSql('ALTER TABLE user ADD address LONGTEXT DEFAULT NULL, DROP establishment_id, DROP company, DROP phone, DROP is_apprentissage');
    }
}
