<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make phone column required in member table
 */
final class Version20261023000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make phone column required in member table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE member ALTER phone SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE member ALTER phone DROP NOT NULL');
    }
}