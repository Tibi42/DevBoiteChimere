<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at column to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP created_at');
    }
}
