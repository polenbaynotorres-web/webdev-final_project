<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds pending reservation approval while preserving existing reservation statuses.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE reservations MODIFY status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE reservations SET status = 'confirmed' WHERE status = 'pending'");
        $this->addSql("ALTER TABLE reservations MODIFY status ENUM('confirmed','cancelled','completed') NOT NULL DEFAULT 'confirmed'");
    }
}
