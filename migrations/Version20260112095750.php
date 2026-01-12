<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112095750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ttmclassroom DROP FOREIGN KEY FK_990FF6667D6F9B87');
        $this->addSql('ALTER TABLE ttmclassroom DROP FOREIGN KEY FK_990FF6666278D5A8');
        $this->addSql('DROP TABLE ttmclassroom');
        $this->addSql('ALTER TABLE diploma ADD disabled_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE school_year ADD disabled_at DATE DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ttmclassroom (id INT AUTO_INCREMENT NOT NULL, ttm_id INT DEFAULT NULL, classroom_id INT DEFAULT NULL, label VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_990FF6666278D5A8 (classroom_id), INDEX IDX_990FF6667D6F9B87 (ttm_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ttmclassroom ADD CONSTRAINT FK_990FF6667D6F9B87 FOREIGN KEY (ttm_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE ttmclassroom ADD CONSTRAINT FK_990FF6666278D5A8 FOREIGN KEY (classroom_id) REFERENCES classroom (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE diploma DROP disabled_at');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE school_year DROP disabled_at');
    }
}
