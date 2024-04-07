<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240407111022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE csdocument (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(300) NOT NULL, type VARCHAR(50) NOT NULL, url VARCHAR(300) NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_AD3F7D0A7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE csuser (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, firstname VARCHAR(150) NOT NULL, lastname VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_connection DATETIME NOT NULL, date_inscription DATETIME NOT NULL, profile_pict VARCHAR(500) DEFAULT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', tel_number VARCHAR(10) NOT NULL, is_verified TINYINT(1) NOT NULL, professional TINYINT(1) NOT NULL, subscription INT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE csdocument ADD CONSTRAINT FK_AD3F7D0A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES csuser (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE csdocument DROP FOREIGN KEY FK_AD3F7D0A7E3C61F9');
        $this->addSql('DROP TABLE csdocument');
        $this->addSql('DROP TABLE csuser');
    }
}
