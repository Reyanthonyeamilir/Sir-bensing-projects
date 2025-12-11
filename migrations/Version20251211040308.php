<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211040308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at column to user table with proper default values';
    }

    public function up(Schema $schema): void
    {
        // First add the column as nullable
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL');
        
        // Set current date for all existing users
        $this->addSql('UPDATE user SET created_at = NOW() WHERE created_at IS NULL');
        
        // Now make the column NOT NULL
        $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP created_at');
    }
}