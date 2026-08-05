<?php

declare(strict_types=1);

namespace App\Twig;

use App\Domain\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pilotis_unread_notifications_count', $this->getUnreadCount(...)),
            new TwigFunction('pilotis_recent_notifications', $this->getRecent(...)),
        ];
    }

    public function getUnreadCount(): int
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return 0;
        }

        return $this->notificationRepository->countUnread($user);
    }

    /** @return list<\App\Domain\Entity\Notification> */
    public function getRecent(int $limit = 5): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return [];
        }

        return $this->notificationRepository->findUnreadByUser($user, $limit);
    }
}
