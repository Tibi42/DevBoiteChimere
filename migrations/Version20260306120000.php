<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table carousel_slide pour le carousel de la page d'accueil.
 */
final class Version20260306120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create carousel_slide table for homepage carousel';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $isSqlite = $platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform;

        if ($isSqlite) {
            $this->addSql('CREATE TABLE carousel_slide (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                position SMALLINT DEFAULT 0 NOT NULL,
                tag VARCHAR(128) NOT NULL,
                tag_color VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                date VARCHAR(128) NOT NULL,
                btn_text VARCHAR(64) NOT NULL,
                btn_class VARCHAR(255) NOT NULL,
                btn_url VARCHAR(500) DEFAULT NULL
            )');
        } else {
            $this->addSql('CREATE TABLE carousel_slide (
                id INT AUTO_INCREMENT NOT NULL,
                position SMALLINT DEFAULT 0 NOT NULL,
                tag VARCHAR(128) NOT NULL,
                tag_color VARCHAR(64) NOT NULL,
                title VARCHAR(255) NOT NULL,
                date VARCHAR(128) NOT NULL,
                btn_text VARCHAR(64) NOT NULL,
                btn_class VARCHAR(255) NOT NULL,
                btn_url VARCHAR(500) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE carousel_slide');
    }
}
