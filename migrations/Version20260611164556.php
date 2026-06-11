<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611164556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE error_log (id INT AUTO_INCREMENT NOT NULL, occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', http_method VARCHAR(10) NOT NULL, url LONGTEXT NOT NULL, request_payload JSON DEFAULT NULL, error_message LONGTEXT NOT NULL, stack_trace LONGTEXT NOT NULL, status_code INT NOT NULL, user_id INT DEFAULT NULL, user_email VARCHAR(255) DEFAULT NULL, user_roles JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classroom DROP training_contact');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE error_log');
        $this->addSql('ALTER TABLE classroom ADD training_contact JSON DEFAULT NULL');
    }
}
