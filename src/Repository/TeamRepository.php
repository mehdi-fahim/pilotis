<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Team;
use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /** @return list<Team> */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.members', 'm')
            ->andWhere('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
