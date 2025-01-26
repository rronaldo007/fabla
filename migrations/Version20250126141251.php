<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250126141251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission_workflow DROP FOREIGN KEY FK_A590E6C2CB716C7');
        $this->addSql('DROP INDEX IDX_A590E6C2CB716C7 ON submission_workflow');
        $this->addSql('ALTER TABLE submission_workflow CHANGE yes_id submission_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE submission_workflow ADD CONSTRAINT FK_A590E6CE1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('CREATE INDEX IDX_A590E6CE1FD4933 ON submission_workflow (submission_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission_workflow DROP FOREIGN KEY FK_A590E6CE1FD4933');
        $this->addSql('DROP INDEX IDX_A590E6CE1FD4933 ON submission_workflow');
        $this->addSql('ALTER TABLE submission_workflow CHANGE submission_id yes_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE submission_workflow ADD CONSTRAINT FK_A590E6C2CB716C7 FOREIGN KEY (yes_id) REFERENCES submission (id)');
        $this->addSql('CREATE INDEX IDX_A590E6C2CB716C7 ON submission_workflow (yes_id)');
    }
}
