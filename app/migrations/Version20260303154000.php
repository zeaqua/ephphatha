<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add church_role column to member table
 */
final class Version20260303154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add church_role column to member table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE member ADD church_role VARCHAR(50) DEFAULT 'Член церкви' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE member DROP church_role');
    }
}
