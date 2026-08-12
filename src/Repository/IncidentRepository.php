<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Department;
use App\Domain\Entity\Incident;
use App\Domain\Enum\IncidentStatus;
use App\Domain\Enum\Priority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Incident>
 */
class IncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Incident::class);
    }

    public function countOpen(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.status NOT IN (:closedStatuses)')
            ->setParameter('closedStatuses', [IncidentStatus::RESOLVED->value, IncidentStatus::CLOSED->value])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCriticalOpen(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.status NOT IN (:closedStatuses)')
            ->andWhere('i.priority = :critical')
            ->setParameter('closedStatuses', [IncidentStatus::RESOLVED->value, IncidentStatus::CLOSED->value])
            ->setParameter('critical', Priority::CRITICAL->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<Incident> */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.dueDate IS NOT NULL')
            ->andWhere('i.dueDate < :today')
            ->andWhere('i.status NOT IN (:closedStatuses)')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('closedStatuses', [IncidentStatus::RESOLVED->value, IncidentStatus::CLOSED->value])
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Incident> */
    public function findRecent(int $limit = 8): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.openedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countResolvedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.resolvedAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpenedBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.openedAt >= :start')
            ->andWhere('i.openedAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countResolvedBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.resolvedAt >= :start')
            ->andWhere('i.resolvedAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Incident>
     */
    public function findFiltered(
        ?string $q = null,
        ?IncidentStatus $status = null,
        ?Priority $priority = null,
        ?Department $department = null,
        bool $overdueOnly = false,
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.department', 'd')->addSelect('d')
            ->leftJoin('i.assignedActors', 'a')->addSelect('a')
            ->orderBy('i.openedAt', 'DESC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('LOWER(i.title) LIKE :q OR LOWER(i.reference) LIKE :q OR LOWER(COALESCE(i.description, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($status !== null) {
            $qb->andWhere('i.status = :status')
                ->setParameter('status', $status->value);
        }

        if ($priority !== null) {
            $qb->andWhere('i.priority = :priority')
                ->setParameter('priority', $priority->value);
        }

        if ($department !== null) {
            $qb->andWhere('i.department = :department')
                ->setParameter('department', $department);
        }

        if ($overdueOnly) {
            $qb->andWhere('i.dueDate IS NOT NULL')
                ->andWhere('i.dueDate < :today')
                ->andWhere('i.status NOT IN (:closedStatuses)')
                ->setParameter('today', new \DateTimeImmutable('today'))
                ->setParameter('closedStatuses', [IncidentStatus::RESOLVED->value, IncidentStatus::CLOSED->value]);
        }

        return $qb->getQuery()->getResult();
    }
}
