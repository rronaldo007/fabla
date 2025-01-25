<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250125230251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidate_profile ADD user_profile_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AE6B9DD454 FOREIGN KEY (user_profile_id) REFERENCES user_profile (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E8607AE6B9DD454 ON candidate_profile (user_profile_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AE6B9DD454');
        $this->addSql('DROP INDEX UNIQ_E8607AE6B9DD454 ON candidate_profile');
        $this->addSql('ALTER TABLE candidate_profile DROP user_profile_id');
    }
}
