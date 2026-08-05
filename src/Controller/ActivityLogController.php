<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activity')]
final class ActivityLogController extends AbstractController
{
    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
    ) {
    }

    #[Route('', name: 'app_activity_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('activity/index.html.twig', [
            'activities' => $this->activityLogRepository->findRecent(100),
        ]);
    }
}
