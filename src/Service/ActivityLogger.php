<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\ActivityLog;
use App\Domain\Entity\User;
use App\Repository\ActivityLogRepository;

final class ActivityLogger
{
    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
    ) {
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function log(string $action, object $entity, ?User $user = null, ?array $metadata = null): ActivityLog
    {
        $entityId = $this->resolveEntityId($entity);

        $activityLog = (new ActivityLog())
            ->setAction($action)
            ->setEntityType($this->resolveEntityType($entity))
            ->setEntityId($entityId)
            ->setUser($user)
            ->setMetadata($metadata);

        $entityManager = $this->activityLogRepository->getEntityManager();
        $entityManager->persist($activityLog);
        $entityManager->flush();

        return $activityLog;
    }

    private function resolveEntityType(object $entity): string
    {
        return (new \ReflectionClass($entity))->getShortName();
    }

    private function resolveEntityId(object $entity): int
    {
        if (!method_exists($entity, 'getId')) {
            throw new \InvalidArgumentException(sprintf('Entity of type "%s" must expose a getId() method.', $entity::class));
        }

        $id = $entity->getId();

        if (!is_int($id) || $id <= 0) {
            throw new \InvalidArgumentException(sprintf('Entity of type "%s" must be persisted before logging.', $entity::class));
        }

        return $id;
    }
}
