<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240503075459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cs_addons (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cs_apartment_cs_addons (cs_apartment_id INT NOT NULL, cs_addons_id INT NOT NULL, INDEX IDX_6B26F50150DC604D (cs_apartment_id), INDEX IDX_6B26F50154ACA503 (cs_addons_id), PRIMARY KEY(cs_apartment_id, cs_addons_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons ADD CONSTRAINT FK_6B26F50150DC604D FOREIGN KEY (cs_apartment_id) REFERENCES cs_apartment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons ADD CONSTRAINT FK_6B26F50154ACA503 FOREIGN KEY (cs_addons_id) REFERENCES cs_addons (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cs_user ADD is_ban TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_apartment_cs_addons DROP FOREIGN KEY FK_6B26F50150DC604D');
        $this->addSql('ALTER TABLE cs_apartment_cs_addons DROP FOREIGN KEY FK_6B26F50154ACA503');
        $this->addSql('DROP TABLE cs_addons');
        $this->addSql('DROP TABLE cs_apartment_cs_addons');
        $this->addSql('ALTER TABLE cs_user DROP is_ban');
    }
}
