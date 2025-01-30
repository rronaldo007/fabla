<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250127165700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidate_profile (id INT AUTO_INCREMENT NOT NULL, current_school_id INT DEFAULT NULL, specialization_id INT DEFAULT NULL, nationality_id INT DEFAULT NULL, user_profile_id INT DEFAULT NULL, program_entry_date DATE NOT NULL, current_year DATE NOT NULL, student_card_path VARCHAR(255) NOT NULL, cv VARCHAR(255) DEFAULT NULL, INDEX IDX_E8607AE180CF642 (current_school_id), INDEX IDX_E8607AEFA846217 (specialization_id), INDEX IDX_E8607AE1C9DA55 (nationality_id), UNIQUE INDEX UNIQ_E8607AE6B9DD454 (user_profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nationality (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE school (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, location LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specialization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subject_study (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, video_presantation VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, candidate_profile_id INT NOT NULL, subject_id INT DEFAULT NULL, identifier VARCHAR(10) NOT NULL, current_state VARCHAR(100) NOT NULL, is_submission_accepted TINYINT(1) NOT NULL, is_candidate_accepted TINYINT(1) NOT NULL, INDEX IDX_DB055AF3FE3D0586 (candidate_profile_id), UNIQUE INDEX UNIQ_DB055AF323EDC87 (subject_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE submission_workflow (id INT AUTO_INCREMENT NOT NULL, submission_id INT DEFAULT NULL, state VARCHAR(100) NOT NULL, transltioned_at DATE DEFAULT NULL, INDEX IDX_A590E6CE1FD4933 (submission_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, role_id INT NOT NULL, email VARCHAR(150) NOT NULL, password VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, is_validated TINYINT(1) NOT NULL, current_place VARCHAR(50) NOT NULL, email_validation_token VARCHAR(100) DEFAULT NULL, email_validation_token_expires_at DATETIME DEFAULT NULL, INDEX IDX_8D93D649D60322AC (role_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_profile (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(15) DEFAULT NULL, address LONGTEXT DEFAULT NULL, date_of_birth DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D95AB405A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workflow_state (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, state VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_12DDA4CFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AE180CF642 FOREIGN KEY (current_school_id) REFERENCES school (id)');
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AEFA846217 FOREIGN KEY (specialization_id) REFERENCES specialization (id)');
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AE1C9DA55 FOREIGN KEY (nationality_id) REFERENCES nationality (id)');
        $this->addSql('ALTER TABLE candidate_profile ADD CONSTRAINT FK_E8607AE6B9DD454 FOREIGN KEY (user_profile_id) REFERENCES user_profile (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3FE3D0586 FOREIGN KEY (candidate_profile_id) REFERENCES candidate_profile (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF323EDC87 FOREIGN KEY (subject_id) REFERENCES subject_study (id)');
        $this->addSql('ALTER TABLE submission_workflow ADD CONSTRAINT FK_A590E6CE1FD4933 FOREIGN KEY (submission_id) REFERENCES submission (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE workflow_state ADD CONSTRAINT FK_12DDA4CFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AE180CF642');
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AEFA846217');
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AE1C9DA55');
        $this->addSql('ALTER TABLE candidate_profile DROP FOREIGN KEY FK_E8607AE6B9DD454');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3FE3D0586');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF323EDC87');
        $this->addSql('ALTER TABLE submission_workflow DROP FOREIGN KEY FK_A590E6CE1FD4933');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
        $this->addSql('ALTER TABLE workflow_state DROP FOREIGN KEY FK_12DDA4CFA76ED395');
        $this->addSql('DROP TABLE candidate_profile');
        $this->addSql('DROP TABLE nationality');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE school');
        $this->addSql('DROP TABLE specialization');
        $this->addSql('DROP TABLE subject_study');
        $this->addSql('DROP TABLE submission');
        $this->addSql('DROP TABLE submission_workflow');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_profile');
        $this->addSql('DROP TABLE workflow_state');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
