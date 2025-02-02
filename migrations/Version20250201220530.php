<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201220530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, jury_id INT DEFAULT NULL, candidat_id INT DEFAULT NULL, note INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_1323A575E560103C (jury_id), INDEX IDX_1323A5758D0EB82 (candidat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575E560103C FOREIGN KEY (jury_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5758D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidate_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575E560103C');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5758D0EB82');
        $this->addSql('DROP TABLE evaluation');
    }
}
