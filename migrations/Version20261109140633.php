<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109140633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline migration for the existing imported rental system database.';
    }

    public function up(Schema $schema): void
    {
        // The existing database schema and data are already imported.
        // This migration records the existing database as the baseline.
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty to protect the existing database.
    }
}
