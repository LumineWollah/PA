<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240611205714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_document DROP FOREIGN KEY FK_AF6FA4F6B83297E7');
        $this->addSql('DROP INDEX IDX_AF6FA4F6B83297E7 ON cs_document');
        $this->addSql('ALTER TABLE cs_document CHANGE reservation_id attached_reserv_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cs_document ADD CONSTRAINT FK_AF6FA4F6A3A2F86D FOREIGN KEY (attached_reserv_id) REFERENCES cs_reservation (id)');
        $this->addSql('CREATE INDEX IDX_AF6FA4F6A3A2F86D ON cs_document (attached_reserv_id)');
        $this->addSql('ALTER TABLE cs_reservation ADD date_creation DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cs_document DROP FOREIGN KEY FK_AF6FA4F6A3A2F86D');
        $this->addSql('DROP INDEX IDX_AF6FA4F6A3A2F86D ON cs_document');
        $this->addSql('ALTER TABLE cs_document CHANGE attached_reserv_id reservation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cs_document ADD CONSTRAINT FK_AF6FA4F6B83297E7 FOREIGN KEY (reservation_id) REFERENCES cs_reservation (id)');
        $this->addSql('CREATE INDEX IDX_AF6FA4F6B83297E7 ON cs_document (reservation_id)');
        $this->addSql('ALTER TABLE cs_reservation DROP date_creation');
    }
}
