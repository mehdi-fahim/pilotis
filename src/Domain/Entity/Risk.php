<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\RiskStatus;
use App\Domain\Trait\TimestampableTrait;
use App\Repository\RiskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RiskRepository::class)]
#[ORM\Table(name: 'risks')]
#[ORM\HasLifecycleCallbacks]
class Risk
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'risks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 5)]
    private int $probability = 3;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 5)]
    private int $impact = 3;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mitigationPlan = null;

    #[ORM\Column(enumType: RiskStatus::class)]
    private RiskStatus $status = RiskStatus::IDENTIFIED;

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

    public function getProbability(): int
    {
        return $this->probability;
    }

    public function setProbability(int $probability): static
    {
        $this->probability = $probability;

        return $this;
    }

    public function getImpact(): int
    {
        return $this->impact;
    }

    public function setImpact(int $impact): static
    {
        $this->impact = $impact;

        return $this;
    }

    public function getMitigationPlan(): ?string
    {
        return $this->mitigationPlan;
    }

    public function setMitigationPlan(?string $mitigationPlan): static
    {
        $this->mitigationPlan = $mitigationPlan;

        return $this;
    }

    public function getStatus(): RiskStatus
    {
        return $this->status;
    }

    public function setStatus(RiskStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScore(): int
    {
        return $this->probability * $this->impact;
    }
}
