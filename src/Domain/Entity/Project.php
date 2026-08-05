<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\HealthStatus;
use App\Domain\Enum\Priority;
use App\Domain\Enum\ProjectStatus;
use App\Domain\Trait\TimestampableTrait;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
#[ORM\HasLifecycleCallbacks]
class Project
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Team $team = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $manager = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $forecastEndDate = null;

    #[ORM\Column(enumType: ProjectStatus::class)]
    private ProjectStatus $status = ProjectStatus::DRAFT;

    #[ORM\Column(enumType: Priority::class)]
    private Priority $priority = Priority::MEDIUM;

    #[ORM\Column(enumType: HealthStatus::class)]
    private HealthStatus $healthStatus = HealthStatus::GREEN;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $budget = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $consumedBudget = '0.00';

    /** @var Collection<int, Task> */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['kanbanOrder' => 'ASC'])]
    private Collection $tasks;

    /** @var Collection<int, Document> */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    /** @var Collection<int, Risk> */
    #[ORM\OneToMany(targetEntity: Risk::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $risks;

    /** @var Collection<int, Decision> */
    #[ORM\OneToMany(targetEntity: Decision::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $decisions;

    /** @var Collection<int, MilestoneReport> */
    #[ORM\OneToMany(targetEntity: MilestoneReport::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $milestoneReports;

    public function __construct()
    {
        $this->startDate = new \DateTimeImmutable();
        $this->tasks = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->risks = new ArrayCollection();
        $this->decisions = new ArrayCollection();
        $this->milestoneReports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getForecastEndDate(): ?\DateTimeImmutable
    {
        return $this->forecastEndDate;
    }

    public function setForecastEndDate(?\DateTimeImmutable $forecastEndDate): static
    {
        $this->forecastEndDate = $forecastEndDate;

        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status;

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

    public function getHealthStatus(): HealthStatus
    {
        return $this->healthStatus;
    }

    public function setHealthStatus(HealthStatus $healthStatus): static
    {
        $this->healthStatus = $healthStatus;

        return $this;
    }

    public function getBudget(): string
    {
        return $this->budget;
    }

    public function setBudget(string $budget): static
    {
        $this->budget = $budget;

        return $this;
    }

    public function getConsumedBudget(): string
    {
        return $this->consumedBudget;
    }

    public function setConsumedBudget(string $consumedBudget): static
    {
        $this->consumedBudget = $consumedBudget;

        return $this;
    }

    /** @return Collection<int, Task> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setProject($this);
        }

        return $this;
    }

    /** @return Collection<int, Document> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /** @return Collection<int, Risk> */
    public function getRisks(): Collection
    {
        return $this->risks;
    }

    /** @return Collection<int, Decision> */
    public function getDecisions(): Collection
    {
        return $this->decisions;
    }

    /** @return Collection<int, MilestoneReport> */
    public function getMilestoneReports(): Collection
    {
        return $this->milestoneReports;
    }

    public function isOverdue(): bool
    {
        if ($this->endDate === null || $this->status === ProjectStatus::COMPLETED) {
            return false;
        }

        return $this->endDate < new \DateTimeImmutable('today');
    }

    public function getBudgetVariancePercent(): float
    {
        $budget = (float) $this->budget;
        if ($budget <= 0) {
            return 0.0;
        }

        return ((float) $this->consumedBudget - $budget) / $budget * 100;
    }

    public function getProgressPercent(): float
    {
        $total = $this->tasks->count();
        if ($total === 0) {
            return 0.0;
        }

        $done = $this->tasks->filter(
            static fn (Task $task): bool => $task->getStatus()->value === 'done'
        )->count();

        return round($done / $total * 100, 1);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
