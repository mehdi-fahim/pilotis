<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Department;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Department>
 */
class DepartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Department::class);
    }

    /**
     * @return list<Department>
     */
    public function findFiltered(?string $q = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->orderBy('d.name', 'ASC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('LOWER(d.name) LIKE :q OR LOWER(COALESCE(d.code, \'\')) LIKE :q OR LOWER(COALESCE(d.description, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
