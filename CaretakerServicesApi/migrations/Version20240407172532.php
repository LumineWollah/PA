<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240407172532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_apartment (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, bedrooms SMALLINT NOT NULL, travelers_max SMALLINT NOT NULL, area DOUBLE PRECISION NOT NULL, is_fullhouse TINYINT(1) NOT NULL, date_creation DATETIME NOT NULL, is_house TINYINT(1) NOT NULL, price DOUBLE PRECISION NOT NULL, apart_number VARCHAR(5) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_A0B1ED5A7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_document (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(300) NOT NULL, type VARCHAR(50) NOT NULL, url VARCHAR(300) NOT NULL, date_creation DATETIME NOT NULL, visibility TINYINT(1) NOT NULL, INDEX IDX_AF6FA4F67E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, firstname VARCHAR(150) NOT NULL, lastname VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_connection DATETIME NOT NULL, date_inscription DATETIME NOT NULL, profile_pict VARCHAR(500) DEFAULT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', tel_number VARCHAR(10) NOT NULL, is_verified TINYINT(1) NOT NULL, professional TINYINT(1) NOT NULL, subscription INT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cs_apartment ADD CONSTRAINT FK_A0B1ED5A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES cs_user (id)');
        $this->addSql('ALTER TABLE cs_document ADD CONSTRAINT FK_AF6FA4F67E3C61F9 FOREIGN KEY (owner_id) REFERENCES cs_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_apartment DROP FOREIGN KEY FK_A0B1ED5A7E3C61F9');
        $this->addSql('ALTER TABLE cs_document DROP FOREIGN KEY FK_AF6FA4F67E3C61F9');
        $this->addSql('DROP TABLE cs_apartment');
        $this->addSql('DROP TABLE cs_document');
        $this->addSql('DROP TABLE cs_user');
    }
}
