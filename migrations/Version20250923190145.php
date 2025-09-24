<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923190145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE behavior_criteria (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, disabled_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE behavior_level (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, level_number INT NOT NULL, disabled_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classroom (id INT AUTO_INCREMENT NOT NULL, school_year_id INT DEFAULT NULL, diploma_id INT DEFAULT NULL, planning VARCHAR(255) NOT NULL, calendar VARCHAR(255) NOT NULL, INDEX IDX_497D309DD2EECC3F (school_year_id), INDEX IDX_497D309DA99ACEB5 (diploma_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE diploma (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE period (id INT AUTO_INCREMENT NOT NULL, schoolyear_id INT NOT NULL, period_number INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, disabled_at DATE DEFAULT NULL, INDEX IDX_C5B81ECE444E1AE8 (schoolyear_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE school_year (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(50) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skill_criteria (id INT AUTO_INCREMENT NOT NULL, diploma_id INT NOT NULL, label VARCHAR(255) NOT NULL, disabled_at DATE DEFAULT NULL, INDEX IDX_F4B7F673A99ACEB5 (diploma_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skill_group (id INT AUTO_INCREMENT NOT NULL, label LONGTEXT NOT NULL, disabled_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skill_level (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, disabled_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_evaluation (id INT AUTO_INCREMENT NOT NULL, student_id INT DEFAULT NULL, validation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', remarks LONGTEXT NOT NULL, INDEX IDX_FEFC4894CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE terms_acceptance (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, school_year_id INT DEFAULT NULL, validation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5937E89A76ED395 (user_id), INDEX IDX_5937E89D2EECC3F (school_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ttmclassroom (id INT AUTO_INCREMENT NOT NULL, ttm_id INT DEFAULT NULL, classroom_id INT DEFAULT NULL, role VARCHAR(100) NOT NULL, INDEX IDX_990FF6667D6F9B87 (ttm_id), INDEX IDX_990FF6666278D5A8 (classroom_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ttmevaluation (id INT AUTO_INCREMENT NOT NULL, ttm_id INT DEFAULT NULL, student_id INT DEFAULT NULL, validation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', remarks LONGTEXT NOT NULL, INDEX IDX_399CFC277D6F9B87 (ttm_id), INDEX IDX_399CFC27CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tutor_evaluation (id INT AUTO_INCREMENT NOT NULL, tutor_id INT DEFAULT NULL, student_id INT DEFAULT NULL, validation_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', strengths LONGTEXT NOT NULL, weaknesses LONGTEXT NOT NULL, goals LONGTEXT NOT NULL, remarks LONGTEXT NOT NULL, INDEX IDX_DBE91853208F64F1 (tutor_id), INDEX IDX_DBE91853CB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tutor_evaluation_behavior (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tutor_evaluation_skill (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tutor_student (id INT AUTO_INCREMENT NOT NULL, tutor_id INT DEFAULT NULL, student_id INT DEFAULT NULL, date_debut_contract DATE NOT NULL, date_fin_contract DATE NOT NULL, INDEX IDX_DFDBA28C208F64F1 (tutor_id), INDEX IDX_DFDBA28CCB944F1A (student_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, classroom_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, address LONGTEXT DEFAULT NULL, disabled_at DATE DEFAULT NULL, INDEX IDX_8D93D6496278D5A8 (classroom_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classroom ADD CONSTRAINT FK_497D309DD2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id)');
        $this->addSql('ALTER TABLE classroom ADD CONSTRAINT FK_497D309DA99ACEB5 FOREIGN KEY (diploma_id) REFERENCES diploma (id)');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE444E1AE8 FOREIGN KEY (schoolyear_id) REFERENCES school_year (id)');
        $this->addSql('ALTER TABLE skill_criteria ADD CONSTRAINT FK_F4B7F673A99ACEB5 FOREIGN KEY (diploma_id) REFERENCES diploma (id)');
        $this->addSql('ALTER TABLE student_evaluation ADD CONSTRAINT FK_FEFC4894CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE terms_acceptance ADD CONSTRAINT FK_5937E89A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE terms_acceptance ADD CONSTRAINT FK_5937E89D2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id)');
        $this->addSql('ALTER TABLE ttmclassroom ADD CONSTRAINT FK_990FF6667D6F9B87 FOREIGN KEY (ttm_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ttmclassroom ADD CONSTRAINT FK_990FF6666278D5A8 FOREIGN KEY (classroom_id) REFERENCES classroom (id)');
        $this->addSql('ALTER TABLE ttmevaluation ADD CONSTRAINT FK_399CFC277D6F9B87 FOREIGN KEY (ttm_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ttmevaluation ADD CONSTRAINT FK_399CFC27CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_evaluation ADD CONSTRAINT FK_DBE91853208F64F1 FOREIGN KEY (tutor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_evaluation ADD CONSTRAINT FK_DBE91853CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_student ADD CONSTRAINT FK_DFDBA28C208F64F1 FOREIGN KEY (tutor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tutor_student ADD CONSTRAINT FK_DFDBA28CCB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496278D5A8 FOREIGN KEY (classroom_id) REFERENCES classroom (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom DROP FOREIGN KEY FK_497D309DD2EECC3F');
        $this->addSql('ALTER TABLE classroom DROP FOREIGN KEY FK_497D309DA99ACEB5');
        $this->addSql('ALTER TABLE period DROP FOREIGN KEY FK_C5B81ECE444E1AE8');
        $this->addSql('ALTER TABLE skill_criteria DROP FOREIGN KEY FK_F4B7F673A99ACEB5');
        $this->addSql('ALTER TABLE student_evaluation DROP FOREIGN KEY FK_FEFC4894CB944F1A');
        $this->addSql('ALTER TABLE terms_acceptance DROP FOREIGN KEY FK_5937E89A76ED395');
        $this->addSql('ALTER TABLE terms_acceptance DROP FOREIGN KEY FK_5937E89D2EECC3F');
        $this->addSql('ALTER TABLE ttmclassroom DROP FOREIGN KEY FK_990FF6667D6F9B87');
        $this->addSql('ALTER TABLE ttmclassroom DROP FOREIGN KEY FK_990FF6666278D5A8');
        $this->addSql('ALTER TABLE ttmevaluation DROP FOREIGN KEY FK_399CFC277D6F9B87');
        $this->addSql('ALTER TABLE ttmevaluation DROP FOREIGN KEY FK_399CFC27CB944F1A');
        $this->addSql('ALTER TABLE tutor_evaluation DROP FOREIGN KEY FK_DBE91853208F64F1');
        $this->addSql('ALTER TABLE tutor_evaluation DROP FOREIGN KEY FK_DBE91853CB944F1A');
        $this->addSql('ALTER TABLE tutor_student DROP FOREIGN KEY FK_DFDBA28C208F64F1');
        $this->addSql('ALTER TABLE tutor_student DROP FOREIGN KEY FK_DFDBA28CCB944F1A');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496278D5A8');
        $this->addSql('DROP TABLE behavior_criteria');
        $this->addSql('DROP TABLE behavior_level');
        $this->addSql('DROP TABLE classroom');
        $this->addSql('DROP TABLE diploma');
        $this->addSql('DROP TABLE period');
        $this->addSql('DROP TABLE school_year');
        $this->addSql('DROP TABLE skill_criteria');
        $this->addSql('DROP TABLE skill_group');
        $this->addSql('DROP TABLE skill_level');
        $this->addSql('DROP TABLE student_evaluation');
        $this->addSql('DROP TABLE terms_acceptance');
        $this->addSql('DROP TABLE ttmclassroom');
        $this->addSql('DROP TABLE ttmevaluation');
        $this->addSql('DROP TABLE tutor_evaluation');
        $this->addSql('DROP TABLE tutor_evaluation_behavior');
        $this->addSql('DROP TABLE tutor_evaluation_skill');
        $this->addSql('DROP TABLE tutor_student');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
