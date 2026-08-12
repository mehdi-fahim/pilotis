<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow multiple actors per incident via incident_actors join table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE incident_actors (
            incident_id INT NOT NULL,
            actor_id INT NOT NULL,
            PRIMARY KEY(incident_id, actor_id)
        )');
        $this->addSql('CREATE INDEX IDX_incident_actors_incident ON incident_actors (incident_id)');
        $this->addSql('CREATE INDEX IDX_incident_actors_actor ON incident_actors (actor_id)');
        $this->addSql('ALTER TABLE incident_actors ADD CONSTRAINT FK_incident_actors_incident FOREIGN KEY (incident_id) REFERENCES incidents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE incident_actors ADD CONSTRAINT FK_incident_actors_actor FOREIGN KEY (actor_id) REFERENCES actors (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO incident_actors (incident_id, actor_id)
            SELECT id, assigned_actor_id FROM incidents WHERE assigned_actor_id IS NOT NULL');

        $this->addSql('ALTER TABLE incidents DROP CONSTRAINT FK_incidents_actor');
        $this->addSql('DROP INDEX IDX_incidents_actor');
        $this->addSql('ALTER TABLE incidents DROP assigned_actor_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE incidents ADD assigned_actor_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_incidents_actor ON incidents (assigned_actor_id)');
        $this->addSql('ALTER TABLE incidents ADD CONSTRAINT FK_incidents_actor FOREIGN KEY (assigned_actor_id) REFERENCES actors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('UPDATE incidents i
            SET assigned_actor_id = (
                SELECT ia.actor_id FROM incident_actors ia
                WHERE ia.incident_id = i.id
                ORDER BY ia.actor_id ASC
                LIMIT 1
            )');

        $this->addSql('ALTER TABLE incident_actors DROP CONSTRAINT FK_incident_actors_actor');
        $this->addSql('ALTER TABLE incident_actors DROP CONSTRAINT FK_incident_actors_incident');
        $this->addSql('DROP TABLE incident_actors');
    }
}
