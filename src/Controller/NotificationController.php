<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Notification;
use App\Domain\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_notification_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('notification/index.html.twig', [
            'notifications' => $this->notificationRepository->findBy(
                ['user' => $user],
                ['createdAt' => 'DESC'],
                50,
            ),
        ]);
    }

    #[Route('/{id}/read', name: 'app_notification_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || $notification->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('read-notification-' . $notification->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_notification_index');
    }

    #[Route('/read-all', name: 'app_notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('read-all-notifications', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        foreach ($this->notificationRepository->findUnreadByUser($user, 500) as $notification) {
            $notification->setIsRead(true);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');

        return $this->redirectToRoute('app_notification_index');
    }
}
