<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240414183345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_obligatory_service (id INT AUTO_INCREMENT NOT NULL, apartment_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_B891A5C7176DFE85 (apartment_id), INDEX IDX_B891A5C7ED5CA9E6 (service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_reviews (id INT AUTO_INCREMENT NOT NULL, service_id INT DEFAULT NULL, apartment_id INT DEFAULT NULL, author_id INT NOT NULL, content LONGTEXT NOT NULL, rate SMALLINT NOT NULL, post_date DATETIME NOT NULL, INDEX IDX_686EFD0EED5CA9E6 (service_id), INDEX IDX_686EFD0E176DFE85 (apartment_id), INDEX IDX_686EFD0EF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_service (id INT AUTO_INCREMENT NOT NULL, provider_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_E0838CD3A53A8AA (provider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cs_obligatory_service ADD CONSTRAINT FK_B891A5C7176DFE85 FOREIGN KEY (apartment_id) REFERENCES cs_apartment (id)');
        $this->addSql('ALTER TABLE cs_obligatory_service ADD CONSTRAINT FK_B891A5C7ED5CA9E6 FOREIGN KEY (service_id) REFERENCES cs_service (id)');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0EED5CA9E6 FOREIGN KEY (service_id) REFERENCES cs_service (id)');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0E176DFE85 FOREIGN KEY (apartment_id) REFERENCES cs_apartment (id)');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0EF675F31B FOREIGN KEY (author_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_service ADD CONSTRAINT FK_E0838CD3A53A8AA FOREIGN KEY (provider_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_reservation ADD service_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cs_reservation ADD CONSTRAINT FK_4199FEBBED5CA9E6 FOREIGN KEY (service_id) REFERENCES cs_service (id)');
        $this->addSql('CREATE INDEX IDX_4199FEBBED5CA9E6 ON cs_reservation (service_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_reservation DROP FOREIGN KEY FK_4199FEBBED5CA9E6');
        $this->addSql('ALTER TABLE cs_obligatory_service DROP FOREIGN KEY FK_B891A5C7176DFE85');
        $this->addSql('ALTER TABLE cs_obligatory_service DROP FOREIGN KEY FK_B891A5C7ED5CA9E6');
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0EED5CA9E6');
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0E176DFE85');
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0EF675F31B');
        $this->addSql('ALTER TABLE cs_service DROP FOREIGN KEY FK_E0838CD3A53A8AA');
        $this->addSql('DROP TABLE cs_obligatory_service');
        $this->addSql('DROP TABLE cs_reviews');
        $this->addSql('DROP TABLE cs_service');
        $this->addSql('DROP INDEX IDX_4199FEBBED5CA9E6 ON cs_reservation');
        $this->addSql('ALTER TABLE cs_reservation DROP service_id');
    }
}
