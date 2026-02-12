<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212093631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE member ADD diakon_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT FK_70E4FA783E4BBBD0 FOREIGN KEY (diakon_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_70E4FA783E4BBBD0 ON member (diakon_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA783E4BBBD0');
        $this->addSql('DROP INDEX IDX_70E4FA783E4BBBD0');
        $this->addSql('ALTER TABLE member DROP diakon_id');
    }
}
