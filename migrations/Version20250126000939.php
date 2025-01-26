<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250126000939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(10) NOT NULL, current_state VARCHAR(100) NOT NULL, is_submission_accepted TINYINT(1) NOT NULL, is_candidate_accepted TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submission_workflow (id INT AUTO_INCREMENT NOT NULL, yes_id INT DEFAULT NULL, state VARCHAR(100) NOT NULL, transltioned_at DATE DEFAULT NULL, INDEX IDX_A590E6C2CB716C7 (yes_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE submission_workflow ADD CONSTRAINT FK_A590E6C2CB716C7 FOREIGN KEY (yes_id) REFERENCES submission (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission_workflow DROP FOREIGN KEY FK_A590E6C2CB716C7');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE submission_workflow');
    }
}
