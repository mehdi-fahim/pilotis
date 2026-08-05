<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\Entity\Comment;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist)]
final class NotificationSubscriber
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Comment && $entity->getTask()->getAssignee() !== null) {
            $assignee = $entity->getTask()->getAssignee();
            if ($assignee->getId() !== $entity->getAuthor()->getId()) {
                $this->notificationService->notify(
                    $assignee,
                    'comment',
                    'Nouveau commentaire',
                    sprintf('Un commentaire a été ajouté sur « %s ».', $entity->getTask()->getTitle()),
                    null
                );
            }
        }
    }
}
