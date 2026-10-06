<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow multiple actors per task via task_actors join table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_actors (
            task_id INT NOT NULL,
            actor_id INT NOT NULL,
            PRIMARY KEY(task_id, actor_id)
        )');
        $this->addSql('CREATE INDEX IDX_task_actors_task ON task_actors (task_id)');
        $this->addSql('CREATE INDEX IDX_task_actors_actor ON task_actors (actor_id)');
        $this->addSql('ALTER TABLE task_actors ADD CONSTRAINT FK_task_actors_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE task_actors ADD CONSTRAINT FK_task_actors_actor FOREIGN KEY (actor_id) REFERENCES actors (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('INSERT INTO task_actors (task_id, actor_id)
            SELECT id, assigned_actor_id FROM tasks WHERE assigned_actor_id IS NOT NULL');

        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597A1F207E6');
        $this->addSql('DROP INDEX IDX_50586597A1F207E6');
        $this->addSql('ALTER TABLE tasks DROP assigned_actor_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tasks ADD assigned_actor_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_50586597A1F207E6 ON tasks (assigned_actor_id)');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597A1F207E6 FOREIGN KEY (assigned_actor_id) REFERENCES actors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('UPDATE tasks t
            SET assigned_actor_id = (
                SELECT ta.actor_id FROM task_actors ta
                WHERE ta.task_id = t.id
                ORDER BY ta.actor_id ASC
                LIMIT 1
            )');

        $this->addSql('ALTER TABLE task_actors DROP CONSTRAINT FK_task_actors_actor');
        $this->addSql('ALTER TABLE task_actors DROP CONSTRAINT FK_task_actors_task');
        $this->addSql('DROP TABLE task_actors');
    }
}
