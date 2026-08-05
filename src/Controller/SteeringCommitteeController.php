<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Project;
use App\Service\SteeringCommitteePdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SteeringCommitteeController extends AbstractController
{
    public function __construct(
        private readonly SteeringCommitteePdfService $pdfService,
    ) {
    }

    #[Route('/projects/{id}/steering-committee/pdf', name: 'app_steering_committee_pdf', methods: ['GET'])]
    public function generatePdf(Project $project): Response
    {
        $pdfContent = $this->pdfService->generate($project);

        return new Response($pdfContent, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="comite-pilotage-%d.pdf"', $project->getId()),
        ]);
    }
}
