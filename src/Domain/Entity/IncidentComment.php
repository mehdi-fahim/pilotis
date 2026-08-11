<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Trait\TimestampableTrait;
use App\Repository\IncidentCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IncidentCommentRepository::class)]
#[ORM\Table(name: 'incident_comments')]
#[ORM\HasLifecycleCallbacks]
class IncidentComment
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Incident $incident;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $content = '';

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

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
