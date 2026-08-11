<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250811200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add incidents table for incident tracking module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE incidents (
            id SERIAL NOT NULL,
            department_id INT DEFAULT NULL,
            assigned_actor_id INT DEFAULT NULL,
            reported_by_id INT DEFAULT NULL,
            reference VARCHAR(30) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(255) NOT NULL,
            priority VARCHAR(255) NOT NULL,
            opened_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_incident_reference ON incidents (reference)');
        $this->addSql('CREATE INDEX IDX_incidents_department ON incidents (department_id)');
        $this->addSql('CREATE INDEX IDX_incidents_actor ON incidents (assigned_actor_id)');
        $this->addSql('CREATE INDEX IDX_incidents_reporter ON incidents (reported_by_id)');
        $this->addSql('ALTER TABLE incidents ADD CONSTRAINT FK_incidents_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE incidents ADD CONSTRAINT FK_incidents_actor FOREIGN KEY (assigned_actor_id) REFERENCES actors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE incidents ADD CONSTRAINT FK_incidents_reporter FOREIGN KEY (reported_by_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE incidents DROP CONSTRAINT FK_incidents_department');
        $this->addSql('ALTER TABLE incidents DROP CONSTRAINT FK_incidents_actor');
        $this->addSql('ALTER TABLE incidents DROP CONSTRAINT FK_incidents_reporter');
        $this->addSql('DROP TABLE incidents');
    }
}
