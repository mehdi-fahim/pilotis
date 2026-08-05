<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class ActivityLogSubscriber
{
    private const TRACKED_ENTITIES = [
        'Project', 'Task', 'Team', 'Client', 'Document', 'Comment', 'Risk', 'Decision', 'MilestoneReport',
    ];

    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly Security $security,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->log('created', $args);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->log('updated', $args);
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->log('deleted', $args);
    }

    private function log(string $action, PostPersistEventArgs|PostUpdateEventArgs|PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $shortName = (new \ReflectionClass($entity))->getShortName();

        if (!in_array($shortName, self::TRACKED_ENTITIES, true)) {
            return;
        }

        if (!method_exists($entity, 'getId') || $entity->getId() === null) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        $this->activityLogger->log($action, $entity, $user);
    }
}
