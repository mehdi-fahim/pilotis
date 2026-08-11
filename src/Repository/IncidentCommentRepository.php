<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\IncidentComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<IncidentComment> */
class IncidentCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncidentComment::class);
    }
}
