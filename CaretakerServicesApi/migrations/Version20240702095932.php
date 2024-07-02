<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240702095932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_reviews ADD reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE cs_reviews ADD CONSTRAINT FK_686EFD0EB83297E7 FOREIGN KEY (reservation_id) REFERENCES cs_reservation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_686EFD0EB83297E7 ON cs_reviews (reservation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_reviews DROP FOREIGN KEY FK_686EFD0EB83297E7');
        $this->addSql('DROP INDEX UNIQ_686EFD0EB83297E7 ON cs_reviews');
        $this->addSql('ALTER TABLE cs_reviews DROP reservation_id');
    }
}
