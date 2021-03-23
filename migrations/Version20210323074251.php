<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210323074251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD email_confirmed TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('CREATE INDEX IDX_CREATED_AT ON user (created_at)');
        $this->addSql('CREATE INDEX IDX_UPDATED_AT ON user (updated_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_CREATED_AT ON user');
        $this->addSql('DROP INDEX IDX_UPDATED_AT ON user');
        $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at, DROP email_confirmed');
    }
}
