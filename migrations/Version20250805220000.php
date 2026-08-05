<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250805220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Internal PM pivot: departments, actors, optional project relations, task startDate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE departments (id SERIAL NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(20) DEFAULT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');

        $this->addSql('CREATE TABLE actors (id SERIAL NOT NULL, department_id INT DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, role VARCHAR(100) DEFAULT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3AD684AEAE80F5DF ON actors (department_id)');

        $this->addSql('ALTER TABLE projects ALTER client_id DROP NOT NULL');
        $this->addSql('ALTER TABLE projects ALTER team_id DROP NOT NULL');
        $this->addSql('ALTER TABLE projects ALTER manager_id DROP NOT NULL');

        $this->addSql('ALTER TABLE tasks ADD start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE tasks ADD assigned_actor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tasks ADD department_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_50586597A1F207E6 ON tasks (assigned_actor_id)');
        $this->addSql('CREATE INDEX IDX_50586597AE80F5DF ON tasks (department_id)');

        $this->addSql('ALTER TABLE actors ADD CONSTRAINT FK_3AD684AEAE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597A1F207E6 FOREIGN KEY (assigned_actor_id) REFERENCES actors (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597A1F207E6');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597AE80F5DF');
        $this->addSql('ALTER TABLE actors DROP CONSTRAINT FK_3AD684AEAE80F5DF');

        $this->addSql('ALTER TABLE tasks DROP start_date');
        $this->addSql('ALTER TABLE tasks DROP assigned_actor_id');
        $this->addSql('ALTER TABLE tasks DROP department_id');

        $this->addSql('ALTER TABLE projects ALTER client_id SET NOT NULL');
        $this->addSql('ALTER TABLE projects ALTER team_id SET NOT NULL');
        $this->addSql('ALTER TABLE projects ALTER manager_id SET NOT NULL');

        $this->addSql('DROP TABLE actors');
        $this->addSql('DROP TABLE departments');
    }
}
