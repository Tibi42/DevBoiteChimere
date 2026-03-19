<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table inscription pour les inscriptions aux événements.
 */
final class Version20260201130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create inscription table for event registration';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;

        if ($isSqlite) {
            $this->addSql('CREATE TABLE inscription (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                activity_id INTEGER NOT NULL,
                participant_name VARCHAR(255) NOT NULL,
                participant_email VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                CONSTRAINT FK_inscription_activity FOREIGN KEY (activity_id) REFERENCES activity (id) ON DELETE CASCADE
            )');
            $this->addSql('CREATE INDEX IDX_inscription_activity ON inscription (activity_id)');
        } else {
            $this->addSql('CREATE TABLE inscription (
                id INT AUTO_INCREMENT NOT NULL,
                activity_id INT NOT NULL,
                participant_name VARCHAR(255) NOT NULL,
                participant_email VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_inscription_activity (activity_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_inscription_activity FOREIGN KEY (activity_id) REFERENCES activity (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inscription');
    }
}
