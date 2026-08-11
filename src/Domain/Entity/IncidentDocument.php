<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Trait\TimestampableTrait;
use App\Repository\IncidentDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IncidentDocumentRepository::class)]
#[ORM\Table(name: 'incident_documents')]
#[ORM\HasLifecycleCallbacks]
class IncidentDocument
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Incident $incident;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $originalFilename = '';

    #[ORM\Column(length: 255)]
    private string $storedFilename = '';

    #[ORM\Column(length: 100)]
    private string $mimeType = 'application/octet-stream';

    #[ORM\Column]
    private int $size = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $uploadedBy;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIncident(): Incident
    {
        return $this->incident;
    }

    public function setIncident(Incident $incident): static
    {
        $this->incident = $incident;

        return $this;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getStoredFilename(): string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(string $storedFilename): static
    {
        $this->storedFilename = $storedFilename;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getUploadedBy(): User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }
}
