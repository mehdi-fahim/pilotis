<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\IncidentStatus;
use App\Domain\Enum\Priority;
use App\Domain\Trait\TimestampableTrait;
use App\Repository\IncidentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IncidentRepository::class)]
#[ORM\Table(name: 'incidents')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uniq_incident_reference', columns: ['reference'])]
class Incident
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(enumType: IncidentStatus::class)]
    private IncidentStatus $status = IncidentStatus::OPEN;

    #[ORM\Column(enumType: Priority::class)]
    private Priority $priority = Priority::MEDIUM;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Department $department = null;

    /** @var Collection<int, Actor> */
    #[ORM\ManyToMany(targetEntity: Actor::class)]
    #[ORM\JoinTable(name: 'incident_actors')]
    #[ORM\JoinColumn(name: 'incident_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'actor_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['lastName' => 'ASC', 'firstName' => 'ASC'])]
    private Collection $assignedActors;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reportedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $openedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $discoveredAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $solution = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reproductionSteps = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $impact = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $environment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rootCause = null;

    /** @var Collection<int, IncidentComment> */
    #[ORM\OneToMany(mappedBy: 'incident', targetEntity: IncidentComment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    /** @var Collection<int, IncidentDocument> */
    #[ORM\OneToMany(mappedBy: 'incident', targetEntity: IncidentDocument::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $documents;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->openedAt = $now;
        $this->discoveredAt = $now;
        $this->comments = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->assignedActors = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function initializeReference(): void
    {
        if ($this->reference === '') {
            $this->reference = 'INC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): IncidentStatus
    {
        return $this->status;
    }

    public function setStatus(IncidentStatus $status): static
    {
        $this->status = $status;

        if (!$status->isOpen()) {
            $this->resolvedAt ??= new \DateTimeImmutable();
        }

        return $this;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(Priority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

        return $this;
    }

    /** @return Collection<int, Actor> */
    public function getAssignedActors(): Collection
    {
        return $this->assignedActors;
    }

    public function addAssignedActor(Actor $actor): static
    {
        if (!$this->assignedActors->contains($actor)) {
            $this->assignedActors->add($actor);
        }

        return $this;
    }

    public function removeAssignedActor(Actor $actor): static
    {
        $this->assignedActors->removeElement($actor);

        return $this;
    }

    /**
     * @param iterable<Actor> $actors
     */
    public function syncAssignedActors(iterable $actors): static
    {
        $incoming = [];
        foreach ($actors as $actor) {
            $incoming[$actor->getId() ?? spl_object_id($actor)] = $actor;
            $this->addAssignedActor($actor);
        }

        foreach ($this->assignedActors->toArray() as $actor) {
            $key = $actor->getId() ?? spl_object_id($actor);
            if (!isset($incoming[$key])) {
                $this->removeAssignedActor($actor);
            }
        }

        return $this;
    }

    public function getReportedBy(): ?User
    {
        return $this->reportedBy;
    }

    public function setReportedBy(?User $reportedBy): static
    {
        $this->reportedBy = $reportedBy;

        return $this;
    }

    public function getOpenedAt(): \DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function setOpenedAt(\DateTimeImmutable $openedAt): static
    {
        $this->openedAt = $openedAt;

        return $this;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getDiscoveredAt(): \DateTimeImmutable
    {
        return $this->discoveredAt;
    }

    public function setDiscoveredAt(\DateTimeImmutable $discoveredAt): static
    {
        $this->discoveredAt = $discoveredAt;

        return $this;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(?string $solution): static
    {
        $this->solution = $solution;

        return $this;
    }

    public function getReproductionSteps(): ?string
    {
        return $this->reproductionSteps;
    }

    public function setReproductionSteps(?string $reproductionSteps): static
    {
        $this->reproductionSteps = $reproductionSteps;

        return $this;
    }

    public function getImpact(): ?string
    {
        return $this->impact;
    }

    public function setImpact(?string $impact): static
    {
        $this->impact = $impact;

        return $this;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function setEnvironment(?string $environment): static
    {
        $this->environment = $environment;

        return $this;
    }

    public function getRootCause(): ?string
    {
        return $this->rootCause;
    }

    public function setRootCause(?string $rootCause): static
    {
        $this->rootCause = $rootCause;

        return $this;
    }

    /** @return Collection<int, IncidentComment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, IncidentDocument> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function getAssigneeLabel(): string
    {
        if ($this->assignedActors->isEmpty()) {
            return 'Non assigné';
        }

        return implode(', ', $this->assignedActors->map(
            static fn (Actor $actor): string => $actor->getFullName()
        )->toArray());
    }

    public function getDaysUntilDue(): ?int
    {
        if ($this->dueDate === null) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $due = $this->dueDate->setTime(0, 0);

        return (int) $today->diff($due)->format('%r%a');
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null || !$this->status->isOpen()) {
            return false;
        }

        return $this->dueDate < new \DateTimeImmutable('today');
    }
}
