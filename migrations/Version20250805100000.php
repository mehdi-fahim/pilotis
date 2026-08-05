<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250805100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial Pilotis schema — users, teams, projects, tasks, documents, notifications, risks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, roles JSON NOT NULL, is_verified BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON users (email)');

        $this->addSql('CREATE TABLE teams (id SERIAL NOT NULL, owner_id INT NOT NULL, name VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_96CDD5F7E3C61F9 ON teams (owner_id)');

        $this->addSql('CREATE TABLE team_members (id SERIAL NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_team_user ON team_members (team_id, user_id)');
        $this->addSql('CREATE INDEX IDX_BAD9A3C8296CD8AE ON team_members (team_id)');
        $this->addSql('CREATE INDEX IDX_BAD9A3C8A76ED395 ON team_members (user_id)');

        $this->addSql('CREATE TABLE clients (id SERIAL NOT NULL, created_by_id INT NOT NULL, name VARCHAR(150) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, company VARCHAR(150) DEFAULT NULL, address TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C82E74B03D8E604F ON clients (created_by_id)');

        $this->addSql('CREATE TABLE projects (id SERIAL NOT NULL, client_id INT NOT NULL, team_id INT NOT NULL, manager_id INT NOT NULL, name VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, forecast_end_date DATE DEFAULT NULL, status VARCHAR(255) NOT NULL, priority VARCHAR(255) NOT NULL, health_status VARCHAR(255) NOT NULL, budget NUMERIC(12, 2) NOT NULL, consumed_budget NUMERIC(12, 2) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5C93BF3F19EB6921 ON projects (client_id)');
        $this->addSql('CREATE INDEX IDX_5C93BF3F296CD8AE ON projects (team_id)');
        $this->addSql('CREATE INDEX IDX_5C93BF3F783E3463 ON projects (manager_id)');

        $this->addSql('CREATE TABLE tasks (id SERIAL NOT NULL, project_id INT NOT NULL, assignee_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, priority VARCHAR(255) NOT NULL, estimate_minutes INT NOT NULL, time_spent_minutes INT NOT NULL, due_date DATE DEFAULT NULL, kanban_order INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_50586597166D1F9C ON tasks (project_id)');
        $this->addSql('CREATE INDEX IDX_5058659759EC7D60 ON tasks (assignee_id)');

        $this->addSql('CREATE TABLE documents (id SERIAL NOT NULL, project_id INT NOT NULL, uploaded_by_id INT NOT NULL, original_filename VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A2B07288166D1F9C ON documents (project_id)');
        $this->addSql('CREATE INDEX IDX_A2B07288A2B28FE8 ON documents (uploaded_by_id)');

        $this->addSql('CREATE TABLE comments (id SERIAL NOT NULL, task_id INT NOT NULL, author_id INT NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F9E962A8DB60186 ON comments (task_id)');
        $this->addSql('CREATE INDEX IDX_5F9E962AF675F31B ON comments (author_id)');

        $this->addSql('CREATE TABLE activity_logs (id SERIAL NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT NOT NULL, metadata JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_activity_entity ON activity_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX IDX_F6BEE604A76ED395 ON activity_logs (user_id)');

        $this->addSql('CREATE TABLE notifications (id SERIAL NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(200) NOT NULL, message VARCHAR(500) NOT NULL, link VARCHAR(255) DEFAULT NULL, is_read BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6000B0D3A76ED395 ON notifications (user_id)');

        $this->addSql('CREATE TABLE risks (id SERIAL NOT NULL, project_id INT NOT NULL, title VARCHAR(200) NOT NULL, description TEXT DEFAULT NULL, probability INT NOT NULL, impact INT NOT NULL, mitigation_plan TEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_523EFBEF166D1F9C ON risks (project_id)');

        $this->addSql('CREATE TABLE decisions (id SERIAL NOT NULL, project_id INT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(200) NOT NULL, description TEXT NOT NULL, meeting_date DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6385CF9166D1F9C ON decisions (project_id)');
        $this->addSql('CREATE INDEX IDX_6385CF9B03A8386 ON decisions (created_by_id)');

        $this->addSql('CREATE TABLE milestone_reports (id SERIAL NOT NULL, project_id INT NOT NULL, created_by_id INT NOT NULL, title VARCHAR(200) NOT NULL, content TEXT NOT NULL, milestone_date DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F6C7B2B166D1F9C ON milestone_reports (project_id)');
        $this->addSql('CREATE INDEX IDX_8F6C7B2BB03A8386 ON milestone_reports (created_by_id)');

        $this->addSql('CREATE TABLE email_verification_tokens (id SERIAL NOT NULL, user_id INT NOT NULL, token VARCHAR(100) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EFEEBDA55F37A13B ON email_verification_tokens (token)');
        $this->addSql('CREATE INDEX IDX_EFEEBDA5A76ED395 ON email_verification_tokens (user_id)');

        $this->addSql('CREATE TABLE password_reset_tokens (id SERIAL NOT NULL, user_id INT NOT NULL, token VARCHAR(100) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_39668672A5F37A13B ON password_reset_tokens (token)');
        $this->addSql('CREATE INDEX IDX_39668672A76ED395 ON password_reset_tokens (user_id)');

        $this->addSql('ALTER TABLE teams ADD CONSTRAINT FK_96CDD5F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE clients ADD CONSTRAINT FK_C82E74B03D8E604F FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93BF3F19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93BF3F296CD8AE FOREIGN KEY (team_id) REFERENCES teams (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93BF3F783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_5058659759EC7D60 FOREIGN KEY (assignee_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE documents ADD CONSTRAINT FK_A2B07288A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A8DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE activity_logs ADD CONSTRAINT FK_F6BEE604A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE risks ADD CONSTRAINT FK_523EFBEF166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE decisions ADD CONSTRAINT FK_6385CF9166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE decisions ADD CONSTRAINT FK_6385CF9B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE milestone_reports ADD CONSTRAINT FK_8F6C7B2B166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE milestone_reports ADD CONSTRAINT FK_8F6C7B2BB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE email_verification_tokens ADD CONSTRAINT FK_EFEEBDA5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_39668672A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_tokens DROP CONSTRAINT FK_39668672A76ED395');
        $this->addSql('ALTER TABLE email_verification_tokens DROP CONSTRAINT FK_EFEEBDA5A76ED395');
        $this->addSql('ALTER TABLE milestone_reports DROP CONSTRAINT FK_8F6C7B2BB03A8386');
        $this->addSql('ALTER TABLE milestone_reports DROP CONSTRAINT FK_8F6C7B2B166D1F9C');
        $this->addSql('ALTER TABLE decisions DROP CONSTRAINT FK_6385CF9B03A8386');
        $this->addSql('ALTER TABLE decisions DROP CONSTRAINT FK_6385CF9166D1F9C');
        $this->addSql('ALTER TABLE risks DROP CONSTRAINT FK_523EFBEF166D1F9C');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE activity_logs DROP CONSTRAINT FK_F6BEE604A76ED395');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962AF675F31B');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962A8DB60186');
        $this->addSql('ALTER TABLE documents DROP CONSTRAINT FK_A2B07288A2B28FE8');
        $this->addSql('ALTER TABLE documents DROP CONSTRAINT FK_A2B07288166D1F9C');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_5058659759EC7D60');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597166D1F9C');
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93BF3F783E3463');
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93BF3F296CD8AE');
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93BF3F19EB6921');
        $this->addSql('ALTER TABLE clients DROP CONSTRAINT FK_C82E74B03D8E604F');
        $this->addSql('ALTER TABLE team_members DROP CONSTRAINT FK_BAD9A3C8A76ED395');
        $this->addSql('ALTER TABLE team_members DROP CONSTRAINT FK_BAD9A3C8296CD8AE');
        $this->addSql('ALTER TABLE teams DROP CONSTRAINT FK_96CDD5F7E3C61F9');

        $this->addSql('DROP TABLE password_reset_tokens');
        $this->addSql('DROP TABLE email_verification_tokens');
        $this->addSql('DROP TABLE milestone_reports');
        $this->addSql('DROP TABLE decisions');
        $this->addSql('DROP TABLE risks');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE activity_logs');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE documents');
        $this->addSql('DROP TABLE tasks');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE team_members');
        $this->addSql('DROP TABLE teams');
        $this->addSql('DROP TABLE users');
    }
}
