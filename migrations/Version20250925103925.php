<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925103925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_group ADD diploma_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill_group ADD CONSTRAINT FK_48E8D7F9A99ACEB5 FOREIGN KEY (diploma_id) REFERENCES diploma (id)');
        $this->addSql('CREATE INDEX IDX_48E8D7F9A99ACEB5 ON skill_group (diploma_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_group DROP FOREIGN KEY FK_48E8D7F9A99ACEB5');
        $this->addSql('DROP INDEX IDX_48E8D7F9A99ACEB5 ON skill_group');
        $this->addSql('ALTER TABLE skill_group DROP diploma_id');
    }
}
