<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds active/disabled account status while preserving existing users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD status VARCHAR(20) NOT NULL DEFAULT 'active'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP status');
    }
}
