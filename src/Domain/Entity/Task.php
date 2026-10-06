<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\Priority;
use App\Domain\Enum\TaskStatus;
use App\Domain\Trait\TimestampableTrait;
use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'tasks')]
#[ORM\HasLifecycleCallbacks]
class Task
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignee = null;

    /** @var Collection<int, Actor> */
    #[ORM\ManyToMany(targetEntity: Actor::class, inversedBy: 'tasks')]
    #[ORM\JoinTable(name: 'task_actors')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'actor_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['lastName' => 'ASC', 'firstName' => 'ASC'])]
    private Collection $assignedActors;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Department $department = null;

    #[ORM\Column(enumType: TaskStatus::class)]
    private TaskStatus $status = TaskStatus::TODO;

    #[ORM\Column(enumType: Priority::class)]
    private Priority $priority = Priority::MEDIUM;

    #[ORM\Column]
    private int $estimateMinutes = 0;

    #[ORM\Column]
    private int $timeSpentMinutes = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column]
    private int $kanbanOrder = 0;

    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->assignedActors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): static
    {
        $this->assignee = $assignee;

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

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getAssigneeLabel(): string
    {
        if (!$this->assignedActors->isEmpty()) {
            return implode(', ', $this->assignedActors->map(
                static fn (Actor $actor): string => $actor->getFullName()
            )->toArray());
        }

        if ($this->assignee !== null) {
            return $this->assignee->getFullName();
        }

        return '—';
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): static
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

    public function getEstimateMinutes(): int
    {
        return $this->estimateMinutes;
    }

    public function setEstimateMinutes(int $estimateMinutes): static
    {
        $this->estimateMinutes = max(0, $estimateMinutes);

        return $this;
    }

    public function getTimeSpentMinutes(): int
    {
        return $this->timeSpentMinutes;
    }

    public function setTimeSpentMinutes(int $timeSpentMinutes): static
    {
        $this->timeSpentMinutes = max(0, $timeSpentMinutes);

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

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

    public function getKanbanOrder(): int
    {
        return $this->kanbanOrder;
    }

    public function setKanbanOrder(int $kanbanOrder): static
    {
        $this->kanbanOrder = $kanbanOrder;

        return $this;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTask($this);
        }

        return $this;
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null || $this->status === TaskStatus::DONE) {
            return false;
        }

        return $this->dueDate < new \DateTimeImmutable('today');
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
