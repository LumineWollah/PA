<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240404140117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cslessor (id INT AUTO_INCREMENT NOT NULL, c_suser_id INT NOT NULL, professional TINYINT(1) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_BB713B986C6CC33A (c_suser_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cslessor ADD CONSTRAINT FK_BB713B986C6CC33A FOREIGN KEY (c_suser_id) REFERENCES csuser (id)');
        $this->addSql('ALTER TABLE csuser DROP `admin`, CHANGE profile_pict profile_pict VARCHAR(500) DEFAULT NULL, CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cslessor DROP FOREIGN KEY FK_BB713B986C6CC33A');
        $this->addSql('DROP TABLE cslessor');
        $this->addSql('ALTER TABLE csuser ADD `admin` TINYINT(1) NOT NULL, CHANGE profile_pict profile_pict VARCHAR(500) DEFAULT \'NULL\', CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
    }
}
