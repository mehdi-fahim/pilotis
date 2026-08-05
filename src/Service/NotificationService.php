<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Notification;
use App\Domain\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function notify(User $user, string $type, string $title, string $message, ?string $link = null): Notification
    {
        $notification = (new Notification())
            ->setUser($user)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setLink($link);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }
}
