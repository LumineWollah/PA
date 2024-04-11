<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240410113410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_apartment_picture DROP FOREIGN KEY FK_65B3D96B176DFE85');
        $this->addSql('DROP TABLE cs_apartment_picture');
        $this->addSql('ALTER TABLE cs_apartment ADD pictures JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_apartment_picture (id INT AUTO_INCREMENT NOT NULL, apartment_id INT NOT NULL, link VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, pictures JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_65B3D96B176DFE85 (apartment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cs_apartment_picture ADD CONSTRAINT FK_65B3D96B176DFE85 FOREIGN KEY (apartment_id) REFERENCES cs_apartment (id)');
        $this->addSql('ALTER TABLE cs_apartment DROP pictures');
    }
}
