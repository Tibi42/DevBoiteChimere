<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add status and proposed_by_id columns to activity table.
 */
final class Version20260320130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status (varchar 16, default published) and proposed_by_id (FK to user) to activity table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE activity ADD status VARCHAR(16) DEFAULT 'published' NOT NULL");
        $this->addSql('ALTER TABLE activity ADD proposed_by_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_activity_status ON activity (status)');
        $this->addSql('CREATE INDEX IDX_activity_proposed_by_id ON activity (proposed_by_id)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_activity_proposed_by_id FOREIGN KEY (proposed_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_activity_proposed_by_id');
        $this->addSql('DROP INDEX idx_activity_status ON activity');
        $this->addSql('DROP INDEX IDX_activity_proposed_by_id ON activity');
        $this->addSql('ALTER TABLE activity DROP proposed_by_id');
        $this->addSql('ALTER TABLE activity DROP status');
    }
}
