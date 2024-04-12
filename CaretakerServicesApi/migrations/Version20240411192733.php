<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411192733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_occupancy_date (id INT AUTO_INCREMENT NOT NULL, apartment_id INT DEFAULT NULL, user_id INT DEFAULT NULL, starting_date DATETIME NOT NULL, ending_date DATETIME NOT NULL, INDEX IDX_A33C088E176DFE85 (apartment_id), INDEX IDX_A33C088EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cs_occupancy_date ADD CONSTRAINT FK_A33C088E176DFE85 FOREIGN KEY (apartment_id) REFERENCES cs_apartment (id)');
        $this->addSql('ALTER TABLE cs_occupancy_date ADD CONSTRAINT FK_A33C088EA76ED395 FOREIGN KEY (user_id) REFERENCES cs_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_occupancy_date DROP FOREIGN KEY FK_A33C088E176DFE85');
        $this->addSql('ALTER TABLE cs_occupancy_date DROP FOREIGN KEY FK_A33C088EA76ED395');
        $this->addSql('DROP TABLE cs_occupancy_date');
    }
}
