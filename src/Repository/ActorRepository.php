<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Actor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actor>
 */
class ActorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actor::class);
    }

    /**
     * @return list<Actor>
     */
    public function findFiltered(?string $q = null, ?int $departmentId = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.department', 'd')->addSelect('d')
            ->orderBy('a.lastName', 'ASC')
            ->addOrderBy('a.firstName', 'ASC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('LOWER(a.firstName) LIKE :q OR LOWER(a.lastName) LIKE :q OR LOWER(COALESCE(a.email, \'\')) LIKE :q OR LOWER(COALESCE(a.role, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($departmentId !== null) {
            $qb->andWhere('IDENTITY(a.department) = :departmentId')
                ->setParameter('departmentId', $departmentId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param list<int> $ids
     * @return list<Actor>
     */
    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        /** @var list<Actor> $actors */
        $actors = $this->createQueryBuilder('a')
            ->andWhere('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $actors;
    }
}
