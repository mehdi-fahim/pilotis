<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250811210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enrich incidents: discovery, solution, comments, documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE incidents ADD discovered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE incidents ADD solution TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE incidents ADD reproduction_steps TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE incidents ADD impact TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE incidents ADD environment VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE incidents ADD root_cause TEXT DEFAULT NULL');
        $this->addSql('UPDATE incidents SET discovered_at = opened_at WHERE discovered_at IS NULL');
        $this->addSql('ALTER TABLE incidents ALTER discovered_at SET NOT NULL');

        $this->addSql('CREATE TABLE incident_comments (
            id SERIAL NOT NULL,
            incident_id INT NOT NULL,
            author_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_incident_comments_incident ON incident_comments (incident_id)');
        $this->addSql('ALTER TABLE incident_comments ADD CONSTRAINT FK_incident_comments_incident FOREIGN KEY (incident_id) REFERENCES incidents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE incident_comments ADD CONSTRAINT FK_incident_comments_author FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE incident_documents (
            id SERIAL NOT NULL,
            incident_id INT NOT NULL,
            uploaded_by_id INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_incident_documents_incident ON incident_documents (incident_id)');
        $this->addSql('ALTER TABLE incident_documents ADD CONSTRAINT FK_incident_documents_incident FOREIGN KEY (incident_id) REFERENCES incidents (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE incident_documents ADD CONSTRAINT FK_incident_documents_user FOREIGN KEY (uploaded_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE incident_documents DROP CONSTRAINT FK_incident_documents_user');
        $this->addSql('ALTER TABLE incident_documents DROP CONSTRAINT FK_incident_documents_incident');
        $this->addSql('DROP TABLE incident_documents');
        $this->addSql('ALTER TABLE incident_comments DROP CONSTRAINT FK_incident_comments_author');
        $this->addSql('ALTER TABLE incident_comments DROP CONSTRAINT FK_incident_comments_incident');
        $this->addSql('DROP TABLE incident_comments');
        $this->addSql('ALTER TABLE incidents DROP discovered_at');
        $this->addSql('ALTER TABLE incidents DROP solution');
        $this->addSql('ALTER TABLE incidents DROP reproduction_steps');
        $this->addSql('ALTER TABLE incidents DROP impact');
        $this->addSql('ALTER TABLE incidents DROP environment');
        $this->addSql('ALTER TABLE incidents DROP root_cause');
    }
}
