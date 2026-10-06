<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\Project;
use App\Domain\Entity\ProjectMeeting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectMeeting>
 */
class ProjectMeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectMeeting::class);
    }

    /** @return list<ProjectMeeting> */
    public function findByProject(Project $project): array
    {
        /** @var list<ProjectMeeting> $meetings */
        $meetings = $this->createQueryBuilder('m')
            ->andWhere('m.project = :project')
            ->setParameter('project', $project)
            ->orderBy('m.heldAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $meetings;
    }

    public function findOneForProject(Project $project, int $id): ?ProjectMeeting
    {
        $meeting = $this->createQueryBuilder('m')
            ->andWhere('m.project = :project')
            ->andWhere('m.id = :id')
            ->setParameter('project', $project)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $meeting instanceof ProjectMeeting ? $meeting : null;
    }

    public function countForProject(Project $project): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
