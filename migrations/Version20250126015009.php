<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250126015009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission ADD candidate_profile_id INT NOT NULL');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3FE3D0586 FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profile (id)');
        $this->addSql('CREATE INDEX IDX_DB055AF3FE3D0586 ON submission (candidate_profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3FE3D0586');
        $this->addSql('DROP INDEX IDX_DB055AF3FE3D0586 ON submission');
        $this->addSql('ALTER TABLE submission DROP candidate_profile_id');
    }
}
