<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Project;
use App\Domain\Entity\Risk;
use App\Domain\Entity\User;
use App\DTO\RiskDto;
use App\Form\RiskFormType;
use App\Repository\RiskRepository;
use App\Service\ActivityLogger;
use App\Service\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{projectId}/risks')]
final class RiskController extends AbstractController
{
    public function __construct(
        private readonly RiskRepository $riskRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectService $projectService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('', name: 'app_risk_index', methods: ['GET'])]
    public function index(int $projectId): Response
    {
        $project = $this->getProject($projectId);

        return $this->render('risk/index.html.twig', [
            'project' => $project,
            'risks' => $this->riskRepository->findBy(['project' => $project], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_risk_new', methods: ['GET', 'POST'])]
    public function new(int $projectId, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $dto = new RiskDto();
        $form = $this->createForm(RiskFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $risk = (new Risk())
                ->setProject($project)
                ->setTitle((string) $dto->title)
                ->setDescription($dto->description)
                ->setProbability($dto->probability)
                ->setImpact($dto->impact)
                ->setMitigationPlan($dto->mitigationPlan)
                ->setStatus($dto->status);

            $this->entityManager->persist($risk);
            $this->projectService->recalculateMetrics($project);
            $this->entityManager->flush();
            $this->activityLogger->log('risk.created', $risk, $this->getUser());

            $this->addFlash('success', 'Risque créé.');

            return $this->redirectToRoute('app_risk_index', ['projectId' => $projectId]);
        }

        return $this->render('risk/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_risk_edit', methods: ['GET', 'POST'])]
    public function edit(int $projectId, Risk $risk, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $this->assertBelongsToProject($risk, $project);

        $dto = new RiskDto();
        $dto->title = $risk->getTitle();
        $dto->description = $risk->getDescription();
        $dto->probability = $risk->getProbability();
        $dto->impact = $risk->getImpact();
        $dto->mitigationPlan = $risk->getMitigationPlan();
        $dto->status = $risk->getStatus();

        $form = $this->createForm(RiskFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $risk
                ->setTitle((string) $dto->title)
                ->setDescription($dto->description)
                ->setProbability($dto->probability)
                ->setImpact($dto->impact)
                ->setMitigationPlan($dto->mitigationPlan)
                ->setStatus($dto->status);

            $this->projectService->recalculateMetrics($project);
            $this->entityManager->flush();
            $this->activityLogger->log('risk.updated', $risk, $this->getUser());

            $this->addFlash('success', 'Risque mis à jour.');

            return $this->redirectToRoute('app_risk_index', ['projectId' => $projectId]);
        }

        return $this->render('risk/edit.html.twig', [
            'project' => $project,
            'risk' => $risk,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_risk_delete', methods: ['POST'])]
    public function delete(int $projectId, Risk $risk, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $this->assertBelongsToProject($risk, $project);

        if (!$this->isCsrfTokenValid('delete-risk-' . $risk->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($risk);
        $this->projectService->recalculateMetrics($project);
        $this->entityManager->flush();

        $this->addFlash('success', 'Risque supprimé.');

        return $this->redirectToRoute('app_risk_index', ['projectId' => $projectId]);
    }

    private function getProject(int $projectId): Project
    {
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        return $project;
    }

    private function assertBelongsToProject(Risk $risk, Project $project): void
    {
        if ($risk->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
