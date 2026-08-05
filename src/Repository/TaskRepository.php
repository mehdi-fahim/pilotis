<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Entity\Task;
use App\Domain\Enum\TaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function countOpen(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.status != :done')
            ->setParameter('done', TaskStatus::DONE->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<Task> */
    public function findByProjectGroupedByStatus(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.project = :project')
            ->setParameter('project', $project)
            ->orderBy('t.status', 'ASC')
            ->addOrderBy('t.kanbanOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Task> */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dueDate IS NOT NULL')
            ->andWhere('t.dueDate < :today')
            ->andWhere('t.status != :done')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('done', TaskStatus::DONE->value)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxKanbanOrder(Project $project, TaskStatus $status): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.kanbanOrder)')
            ->andWhere('t.project = :project')
            ->andWhere('t.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function getTotalTimeSpentMinutes(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('SUM(t.timeSpentMinutes)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<Task> */
    public function findFiltered(?Department $department = null, ?Actor $actor = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->orderBy('t.dueDate', 'ASC')
            ->addOrderBy('t.title', 'ASC');

        if ($department !== null) {
            $qb->andWhere('t.department = :department')
                ->setParameter('department', $department);
        }

        if ($actor !== null) {
            $qb->andWhere('t.assignedActor = :actor')
                ->setParameter('actor', $actor);
        }

        return $qb->getQuery()->getResult();
    }
}
