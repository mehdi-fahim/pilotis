<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /** @return list<Client> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Client>
     */
    public function findFiltered(?string $q = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('LOWER(c.name) LIKE :q OR LOWER(COALESCE(c.company, \'\')) LIKE :q OR LOWER(COALESCE(c.email, \'\')) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
