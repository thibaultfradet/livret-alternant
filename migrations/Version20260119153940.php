<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119153940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE establishment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, terms_conditions_pro LONGTEXT DEFAULT NULL, terms_conditions_alternance LONGTEXT DEFAULT NULL, formation_center JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE school_year ADD establishment_id INT NOT NULL');
        $this->addSql('ALTER TABLE school_year ADD CONSTRAINT FK_FAAAACDA8565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_FAAAACDA8565851 ON school_year (establishment_id)');
        $this->addSql('ALTER TABLE user ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6498565851 ON user (establishment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE school_year DROP FOREIGN KEY FK_FAAAACDA8565851');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6498565851');
        $this->addSql('DROP TABLE establishment');
        $this->addSql('DROP INDEX IDX_FAAAACDA8565851 ON school_year');
        $this->addSql('ALTER TABLE school_year DROP establishment_id');
        $this->addSql('DROP INDEX IDX_8D93D6498565851 ON user');
        $this->addSql('ALTER TABLE user DROP establishment_id');
    }
}
