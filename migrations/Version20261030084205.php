<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030084205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE deployment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, logs CLOB DEFAULT NULL, release_version_id INTEGER NOT NULL, target_environment_id INTEGER NOT NULL, CONSTRAINT FK_EB1255BE265B2DBF FOREIGN KEY (release_version_id) REFERENCES "release" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EB1255BE2C4435BD FOREIGN KEY (target_environment_id) REFERENCES environment (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_EB1255BE265B2DBF ON deployment (release_version_id)');
        $this->addSql('CREATE INDEX IDX_EB1255BE2C4435BD ON deployment (target_environment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE deployment');
    }
}
