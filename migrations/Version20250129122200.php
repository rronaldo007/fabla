<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250129122200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE edition (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, start_publication DATETIME NOT NULL, start_application DATETIME NOT NULL, end_application DATETIME NOT NULL, announcement_date DATETIME NOT NULL, is_current TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_A891181FBB827337 (year), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE jury_profile (id INT AUTO_INCREMENT NOT NULL, user_profile_id INT NOT NULL, profession VARCHAR(255) DEFAULT NULL, mini_cv LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_84408876B9DD454 (user_profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submission_edition (submission_id INT NOT NULL, edition_id INT NOT NULL, INDEX IDX_847083C9E1FD4933 (submission_id), INDEX IDX_847083C974281A5E (edition_id), PRIMARY KEY(submission_id, edition_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE jury_profile ADD CONSTRAINT FK_84408876B9DD454 FOREIGN KEY (user_profile_id) REFERENCES user_profile (id)');
        $this->addSql('ALTER TABLE submission_edition ADD CONSTRAINT FK_847083C9E1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE submission_edition ADD CONSTRAINT FK_847083C974281A5E FOREIGN KEY (edition_id) REFERENCES edition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shared_resource ADD brand_model VARCHAR(255) DEFAULT NULL, ADD commissioning_date DATE DEFAULT NULL, ADD is_archived TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user_profile ADD is_archived TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jury_profile DROP FOREIGN KEY FK_84408876B9DD454');
        $this->addSql('ALTER TABLE submission_edition DROP FOREIGN KEY FK_847083C9E1FD4933');
        $this->addSql('ALTER TABLE submission_edition DROP FOREIGN KEY FK_847083C974281A5E');
        $this->addSql('DROP TABLE edition');
        $this->addSql('DROP TABLE jury_profile');
        $this->addSql('DROP TABLE submission_edition');
        $this->addSql('ALTER TABLE shared_resource DROP brand_model, DROP commissioning_date, DROP is_archived');
        $this->addSql('ALTER TABLE user_profile DROP is_archived');
    }
}
