<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds vehicle return requests and staff inspection records without changing existing rental data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users MODIFY role ENUM('user','admin','staff') NOT NULL DEFAULT 'user'");
        $this->addSql("ALTER TABLE reservations MODIFY status ENUM('confirmed','cancelled','completed') NOT NULL DEFAULT 'confirmed'");
        $this->addSql("CREATE TABLE vehicle_returns (
            id INT AUTO_INCREMENT NOT NULL,
            reservation_id INT UNSIGNED NOT NULL,
            inspected_by_id INT UNSIGNED DEFAULT NULL,
            customer_condition VARCHAR(20) NOT NULL,
            customer_notes LONGTEXT DEFAULT NULL,
            customer_photo VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            inspected_condition VARCHAR(20) DEFAULT NULL,
            inspection_notes LONGTEXT DEFAULT NULL,
            inspection_photo VARCHAR(255) DEFAULT NULL,
            requested_at DATETIME DEFAULT NULL,
            confirmed_at DATETIME DEFAULT NULL,
            UNIQUE INDEX uniq_vehicle_return_reservation (reservation_id),
            INDEX IDX_VEHICLE_RETURNS_INSPECTED_BY (inspected_by_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_VEHICLE_RETURNS_RESERVATION FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE,
            CONSTRAINT FK_VEHICLE_RETURNS_INSPECTED_BY FOREIGN KEY (inspected_by_id) REFERENCES users (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vehicle_returns');
        $this->addSql("ALTER TABLE reservations MODIFY status ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed'");
        $this->addSql("ALTER TABLE users MODIFY role ENUM('user','admin') NOT NULL DEFAULT 'user'");
    }
}
