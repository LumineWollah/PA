<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240610114044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_addons (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_apartment (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, bedrooms SMALLINT NOT NULL, bathrooms SMALLINT NOT NULL, travelers_max SMALLINT NOT NULL, area DOUBLE PRECISION NOT NULL, is_fullhouse TINYINT(1) NOT NULL, date_creation DATETIME NOT NULL, is_house TINYINT(1) NOT NULL, price DOUBLE PRECISION NOT NULL, is_verified TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, address VARCHAR(500) NOT NULL, center_gps JSON NOT NULL COMMENT \'(DC2Type:json)\', city VARCHAR(255) NOT NULL, postal_code VARCHAR(5) NOT NULL, country VARCHAR(255) NOT NULL, main_pict VARCHAR(255) NOT NULL, pictures JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_A0B1ED5A7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_apartment_cs_service (cs_apartment_id INT NOT NULL, cs_service_id INT NOT NULL, INDEX IDX_2E974C9850DC604D (cs_apartment_id), INDEX IDX_2E974C98A094B64 (cs_service_id), PRIMARY KEY(cs_apartment_id, cs_service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_apartment_cs_addons (cs_apartment_id INT NOT NULL, cs_addons_id INT NOT NULL, INDEX IDX_6B26F50150DC604D (cs_apartment_id), INDEX IDX_6B26F50154ACA503 (cs_addons_id), PRIMARY KEY(cs_apartment_id, cs_addons_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(6) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_company (id INT AUTO_INCREMENT NOT NULL, siret_number VARCHAR(14) NOT NULL, company_name VARCHAR(255) NOT NULL, company_email VARCHAR(255) NOT NULL, company_phone VARCHAR(10) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, postal_code VARCHAR(5) NOT NULL, country VARCHAR(255) NOT NULL, center_gps JSON NOT NULL COMMENT \'(DC2Type:json)\', cover_image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_company_cs_category (cs_company_id INT NOT NULL, cs_category_id INT NOT NULL, INDEX IDX_9B61682670CEF854 (cs_company_id), INDEX IDX_9B61682611172A0C (cs_category_id), PRIMARY KEY(cs_company_id, cs_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_document (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(300) NOT NULL, type VARCHAR(50) NOT NULL, url VARCHAR(300) NOT NULL, date_creation DATETIME NOT NULL, visibility TINYINT(1) NOT NULL, INDEX IDX_AF6FA4F67E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_reservation (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, service_id INT DEFAULT NULL, user_id INT DEFAULT NULL, starting_date DATETIME NOT NULL, ending_date DATETIME NOT NULL, price DOUBLE PRECISION NOT NULL, payement_id VARCHAR(255) DEFAULT NULL, active TINYINT(1) NOT NULL, adult_travelers INT DEFAULT NULL, child_travelers INT DEFAULT NULL, baby_travelers INT DEFAULT NULL, unavailability TINYINT(1) NOT NULL, INDEX IDX_4199FEBB176DFE85 (apartment_id), INDEX IDX_4199FEBBED5CA9E6 (service_id), INDEX IDX_4199FEBBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_reservation_cs_service (cs_reservation_id INT NOT NULL, cs_service_id INT NOT NULL, INDEX IDX_72E60F50ACA63353 (cs_reservation_id), INDEX IDX_72E60F50A094B64 (cs_service_id), PRIMARY KEY(cs_reservation_id, cs_service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_reviews (id INT AUTO_INCREMENT NOT NULL, service_id INT DEFAULT NULL, apartment_id INT DEFAULT NULL, author_id INT NOT NULL, content LONGTEXT NOT NULL, rate SMALLINT NOT NULL, post_date DATETIME NOT NULL, INDEX IDX_686EFD0EED5CA9E6 (service_id), INDEX IDX_686EFD0E176DFE85 (apartment_id), INDEX IDX_686EFD0EF675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_service (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, category_id INT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price DOUBLE PRECISION NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, INDEX IDX_E0838CD3979B1AD6 (company_id), INDEX IDX_E0838CD312469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_ticket (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, status VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, date_closing DATETIME DEFAULT NULL, priority VARCHAR(10) NOT NULL, subject VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, client_email VARCHAR(300) DEFAULT NULL, response LONGTEXT DEFAULT NULL, INDEX IDX_52C7AAE2F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_user (id INT AUTO_INCREMENT NOT NULL, company_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, firstname VARCHAR(150) NOT NULL, lastname VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_connection DATETIME NOT NULL, date_inscription DATETIME NOT NULL, profile_pict VARCHAR(500) DEFAULT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', tel_number VARCHAR(10) NOT NULL, is_verified TINYINT(1) NOT NULL, professional TINYINT(1) NOT NULL, subscription INT DEFAULT NULL, is_ban TINYINT(1) NOT NULL, INDEX IDX_BC10A726979B1AD6 (company_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cs_apartment ADD CONSTRAINT FK_A0B1ED5A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_apartment_cs_service ADD CONSTRAINT FK_2E974C9850DC604D FOREIGN KEY (cs_apartment_id) REFERENCES cs_apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_apartment_cs_service ADD CONSTRAINT FK_2E974C98A094B64 FOREIGN KEY (cs_service_id) REFERENCES cs_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons ADD CONSTRAINT FK_6B26F50150DC604D FOREIGN KEY (cs_apartment_id) REFERENCES cs_apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons ADD CONSTRAINT FK_6B26F50154ACA503 FOREIGN KEY (cs_addons_id) REFERENCES cs_addons (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_company_cs_category ADD CONSTRAINT FK_9B61682670CEF854 FOREIGN KEY (cs_company_id) REFERENCES cs_company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_company_cs_category ADD CONSTRAINT FK_9B61682611172A0C FOREIGN KEY (cs_category_id) REFERENCES cs_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_document ADD CONSTRAINT FK_AF6FA4F67E3C61F9 FOREIGN KEY (owner_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_reservation ADD CONSTRAINT FK_4199FEBB176DFE85 FOREIGN KEY (apartment_id) REFERENCES cs_apartment (id)');
        $this->addSql('ALTER TABLE cs_reservation ADD CONSTRAINT FK_4199FEBBED5CA9E6 FOREIGN KEY (service_id) REFERENCES cs_service (id)');
        $this->addSql('ALTER TABLE cs_reservation ADD CONSTRAINT FK_4199FEBBA76ED395 FOREIGN KEY (user_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_reservation_cs_service ADD CONSTRAINT FK_72E60F50ACA63353 FOREIGN KEY (cs_reservation_id) REFERENCES cs_reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_reservation_cs_service ADD CONSTRAINT FK_72E60F50A094B64 FOREIGN KEY (cs_service_id) REFERENCES cs_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0EED5CA9E6 FOREIGN KEY (service_id) REFERENCES cs_service (id)');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0E176DFE85 FOREIGN KEY (apartment_id) REFERENCES cs_apartment (id)');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0EF675F31B FOREIGN KEY (author_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_service ADD CONSTRAINT FK_E0838CD3979B1AD6 FOREIGN KEY (company_id) REFERENCES cs_company (id)');
        $this->addSql('ALTER TABLE cs_service ADD CONSTRAINT FK_E0838CD312469DE2 FOREIGN KEY (category_id) REFERENCES cs_category (id)');
        $this->addSql('ALTER TABLE cs_ticket ADD CONSTRAINT FK_52C7AAE2F675F31B FOREIGN KEY (author_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_user ADD CONSTRAINT FK_BC10A726979B1AD6 FOREIGN KEY (company_id) REFERENCES cs_company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_apartment DROP FOREIGN KEY FK_A0B1ED5A7E3C61F9');
        $this->addSql('ALTER TABLE cs_apartment_cs_service DROP FOREIGN KEY FK_2E974C9850DC604D');
        $this->addSql('ALTER TABLE cs_apartment_cs_service DROP FOREIGN KEY FK_2E974C98A094B64');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons DROP FOREIGN KEY FK_6B26F50150DC604D');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons DROP FOREIGN KEY FK_6B26F50154ACA503');
        $this->addSql('ALTER TABLE cs_company_cs_category DROP FOREIGN KEY FK_9B61682670CEF854');
        $this->addSql('ALTER TABLE cs_company_cs_category DROP FOREIGN KEY FK_9B61682611172A0C');
        $this->addSql('ALTER TABLE cs_document DROP FOREIGN KEY FK_AF6FA4F67E3C61F9');
        $this->addSql('ALTER TABLE cs_reservation DROP FOREIGN KEY FK_4199FEBB176DFE85');
        $this->addSql('ALTER TABLE cs_reservation DROP FOREIGN KEY FK_4199FEBBED5CA9E6');
        $this->addSql('ALTER TABLE cs_reservation DROP FOREIGN KEY FK_4199FEBBA76ED395');
        $this->addSql('ALTER TABLE cs_reservation_cs_service DROP FOREIGN KEY FK_72E60F50ACA63353');
        $this->addSql('ALTER TABLE cs_reservation_cs_service DROP FOREIGN KEY FK_72E60F50A094B64');
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0EED5CA9E6');
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0E176DFE85');
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0EF675F31B');
        $this->addSql('ALTER TABLE cs_service DROP FOREIGN KEY FK_E0838CD3979B1AD6');
        $this->addSql('ALTER TABLE cs_service DROP FOREIGN KEY FK_E0838CD312469DE2');
        $this->addSql('ALTER TABLE cs_ticket DROP FOREIGN KEY FK_52C7AAE2F675F31B');
        $this->addSql('ALTER TABLE cs_user DROP FOREIGN KEY FK_BC10A726979B1AD6');
        $this->addSql('DROP TABLE cs_addons');
        $this->addSql('DROP TABLE cs_apartment');
        $this->addSql('DROP TABLE cs_apartment_cs_service');
        $this->addSql('DROP TABLE cs_apartment_cs_addons');
        $this->addSql('DROP TABLE cs_category');
        $this->addSql('DROP TABLE cs_company');
        $this->addSql('DROP TABLE cs_company_cs_category');
        $this->addSql('DROP TABLE cs_document');
        $this->addSql('DROP TABLE cs_reservation');
        $this->addSql('DROP TABLE cs_reservation_cs_service');
        $this->addSql('DROP TABLE cs_reviews');
        $this->addSql('DROP TABLE cs_service');
        $this->addSql('DROP TABLE cs_ticket');
        $this->addSql('DROP TABLE cs_user');
    }
}
