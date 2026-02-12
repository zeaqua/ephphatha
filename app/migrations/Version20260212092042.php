<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212092042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_member_email');
        $this->addSql('ALTER TABLE member ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE member ADD address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE member ADD comment TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE member ALTER active DROP DEFAULT');
        $this->addSql('ALTER TABLE member ALTER last_update DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE member DROP phone');
        $this->addSql('ALTER TABLE member DROP address');
        $this->addSql('ALTER TABLE member DROP comment');
        $this->addSql('ALTER TABLE member ALTER active SET DEFAULT 1');
        $this->addSql('ALTER TABLE member ALTER last_update SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE UNIQUE INDEX uniq_member_email ON member (email)');
    }
}
