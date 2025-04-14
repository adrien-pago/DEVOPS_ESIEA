<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240414123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all necessary tables';
    }

    public function up(Schema $schema): void
    {
        // Create user table
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');

        // Create quiz table
        $this->addSql('CREATE TABLE quiz (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, creator_id INTEGER NOT NULL, theme VARCHAR(255) NOT NULL, moderated BOOLEAN NOT NULL DEFAULT 0)');
        $this->addSql('CREATE INDEX IDX_A412FA9261220EA6 ON quiz (creator_id)');

        // Create question table
        $this->addSql('CREATE TABLE question (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quiz_id INTEGER NOT NULL, text VARCHAR(255) NOT NULL, choices CLOB NOT NULL, correct_choice INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX IDX_B6F7494E853CD175 ON question (quiz_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE user');
    }
} 