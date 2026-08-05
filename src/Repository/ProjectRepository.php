<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Domain\Enum\ProjectStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<Project> */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate IS NOT NULL')
            ->andWhere('p.endDate < :today')
            ->andWhere('p.status NOT IN (:completed)')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('completed', [ProjectStatus::COMPLETED->value, ProjectStatus::CANCELLED->value])
            ->orderBy('p.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Project> */
    public function findByManager(User $manager): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.manager = :manager')
            ->setParameter('manager', $manager)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return array{budget: float, consumed: float} */
    public function getBudgetSummary(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.budget) as totalBudget, SUM(p.consumedBudget) as totalConsumed')
            ->getQuery()
            ->getSingleResult();

        return [
            'budget' => (float) ($result['totalBudget'] ?? 0),
            'consumed' => (float) ($result['totalConsumed'] ?? 0),
        ];
    }
}
