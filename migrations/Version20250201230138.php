<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201230138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575BB0E8A5E');
        $this->addSql('DROP INDEX IDX_1323A575BB0E8A5E ON evaluation');
        $this->addSql('ALTER TABLE evaluation ADD submission_id INT NOT NULL, DROP submision_id');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575E1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('CREATE INDEX IDX_1323A575E1FD4933 ON evaluation (submission_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575E1FD4933');
        $this->addSql('DROP INDEX IDX_1323A575E1FD4933 ON evaluation');
        $this->addSql('ALTER TABLE evaluation ADD submision_id INT DEFAULT NULL, DROP submission_id');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575BB0E8A5E FOREIGN KEY (submision_id) REFERENCES submission (id)');
        $this->addSql('CREATE INDEX IDX_1323A575BB0E8A5E ON evaluation (submision_id)');
    }
}
