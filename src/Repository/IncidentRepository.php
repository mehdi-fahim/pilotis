<?php

declare(strict_types=1);

namespace App\Repository;

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
}
