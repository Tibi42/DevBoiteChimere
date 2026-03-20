<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add created_by_id column to link activities with their proposer (User).
 */
final class Version20260320123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by_id to activity table (link to user who created the activity)';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;

        if ($isSqlite) {
            // SQLite does not support adding foreign keys via ALTER TABLE in a portable way.
            $this->addSql('ALTER TABLE activity ADD created_by_id INT DEFAULT NULL');
        } else {
            $this->addSql('ALTER TABLE activity ADD created_by_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_activity_created_by_id ON activity (created_by_id)');
            $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_activity_created_by_id FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;

        if ($isSqlite) {
            // Best-effort rollback (SQLite doesn't support DROP COLUMN until newer versions).
            // If you need strict rollback, rerun schema/migrations from a fresh DB.
            return;
        }

        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_activity_created_by_id');
        $this->addSql('DROP INDEX IDX_activity_created_by_id ON activity');
        $this->addSql('ALTER TABLE activity DROP created_by_id');
    }
}

