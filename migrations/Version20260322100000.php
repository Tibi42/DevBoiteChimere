<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add active column to carousel_slide';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE carousel_slide ADD active TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE carousel_slide DROP active');
    }
}
