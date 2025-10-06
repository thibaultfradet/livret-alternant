<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006142559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom ADD principal_teacher_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE classroom ADD CONSTRAINT FK_497D309D98BFDD30 FOREIGN KEY (principal_teacher_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_497D309D98BFDD30 ON classroom (principal_teacher_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classroom DROP FOREIGN KEY FK_497D309D98BFDD30');
        $this->addSql('DROP INDEX IDX_497D309D98BFDD30 ON classroom');
        $this->addSql('ALTER TABLE classroom DROP principal_teacher_id');
    }
}
