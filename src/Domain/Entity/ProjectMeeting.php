<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Trait\TimestampableTrait;
use App\Repository\ProjectMeetingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectMeetingRepository::class)]
#[ORM\Table(name: 'project_meetings')]
#[ORM\Index(name: 'idx_project_meetings_project_held', columns: ['project_id', 'held_at'])]
#[ORM\HasLifecycleCallbacks]
class ProjectMeeting
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'meetings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    private string $title = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $heldAt;

    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    public function __construct()
    {
        $this->heldAt = new \DateTimeImmutable('today');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getHeldAt(): \DateTimeImmutable
    {
        return $this->heldAt;
    }

    public function setHeldAt(\DateTimeImmutable $heldAt): static
    {
        $this->heldAt = $heldAt;

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
