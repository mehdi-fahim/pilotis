<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project meeting notes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project_meetings (
            id SERIAL NOT NULL,
            project_id INT NOT NULL,
            author_id INT DEFAULT NULL,
            title VARCHAR(200) NOT NULL,
            held_at DATE NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_project_meetings_project_held ON project_meetings (project_id, held_at)');
        $this->addSql('CREATE INDEX IDX_project_meetings_author ON project_meetings (author_id)');
        $this->addSql('ALTER TABLE project_meetings ADD CONSTRAINT FK_project_meetings_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_meetings ADD CONSTRAINT FK_project_meetings_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE project_meetings DROP CONSTRAINT FK_project_meetings_author');
        $this->addSql('ALTER TABLE project_meetings DROP CONSTRAINT FK_project_meetings_project');
        $this->addSql('DROP TABLE project_meetings');
    }
}
