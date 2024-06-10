<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240609234027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_company_cs_category (cs_company_id INT NOT NULL, cs_category_id INT NOT NULL, INDEX IDX_9B61682670CEF854 (cs_company_id), INDEX IDX_9B61682611172A0C (cs_category_id), PRIMARY KEY(cs_company_id, cs_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cs_company_cs_category ADD CONSTRAINT FK_9B61682670CEF854 FOREIGN KEY (cs_company_id) REFERENCES cs_company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_company_cs_category ADD CONSTRAINT FK_9B61682611172A0C FOREIGN KEY (cs_category_id) REFERENCES cs_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_service CHANGE cover_image cover_image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_company_cs_category DROP FOREIGN KEY FK_9B61682670CEF854');
        $this->addSql('ALTER TABLE cs_company_cs_category DROP FOREIGN KEY FK_9B61682611172A0C');
        $this->addSql('DROP TABLE cs_company_cs_category');
        $this->addSql('ALTER TABLE cs_service CHANGE cover_image cover_image VARCHAR(255) NOT NULL');
    }
}
