<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\IncidentRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly IncidentRepository $incidentRepository,
    ) {
    }

    #[Route('/recherche', name: 'app_search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = trim($request->query->getString('q'));

        return $this->render('search/index.html.twig', [
            'q' => $q,
            'projects' => $q === '' ? [] : $this->projectRepository->findFiltered($q),
            'incidents' => $q === '' ? [] : $this->incidentRepository->findFiltered($q),
        ]);
    }
}
