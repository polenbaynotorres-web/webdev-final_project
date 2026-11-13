<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds vehicle creator ownership for staff vehicle management.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE vehicles ADD created_by_id INT UNSIGNED DEFAULT NULL'
        );

        $this->addSql(
            'CREATE INDEX IDX_VEHICLES_CREATED_BY ON vehicles (created_by_id)'
        );

        $this->addSql(
            'ALTER TABLE vehicles
             ADD CONSTRAINT FK_VEHICLES_CREATED_BY
             FOREIGN KEY (created_by_id)
             REFERENCES users (id)
             ON DELETE SET NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE vehicles DROP FOREIGN KEY FK_VEHICLES_CREATED_BY'
        );

        $this->addSql(
            'DROP INDEX IDX_VEHICLES_CREATED_BY ON vehicles'
        );

        $this->addSql(
            'ALTER TABLE vehicles DROP created_by_id'
        );
    }
}
