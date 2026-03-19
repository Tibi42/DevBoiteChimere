<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table activity pour le calendrier des événements.
 */
final class Version20260201120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity table for calendar events';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;

        if ($isSqlite) {
            $this->addSql('CREATE TABLE activity (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description CLOB DEFAULT NULL,
                start_at DATETIME NOT NULL,
                end_at DATETIME DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                type VARCHAR(64) DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )');
        } else {
            $this->addSql('CREATE TABLE activity (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                start_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                end_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                location VARCHAR(255) DEFAULT NULL,
                type VARCHAR(64) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity');
    }
}
