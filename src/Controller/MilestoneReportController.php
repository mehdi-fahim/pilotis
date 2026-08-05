<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\MilestoneReport;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\DTO\MilestoneReportDto;
use App\Form\MilestoneReportFormType;
use App\Repository\MilestoneReportRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{projectId}/milestones')]
final class MilestoneReportController extends AbstractController
{
    public function __construct(
        private readonly MilestoneReportRepository $milestoneReportRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('', name: 'app_milestone_index', methods: ['GET'])]
    public function index(int $projectId): Response
    {
        $project = $this->getProject($projectId);

        return $this->render('milestone_report/index.html.twig', [
            'project' => $project,
            'milestones' => $this->milestoneReportRepository->findBy(['project' => $project], ['milestoneDate' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_milestone_new', methods: ['GET', 'POST'])]
    public function new(int $projectId, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $dto = new MilestoneReportDto();
        $dto->milestoneDate = new \DateTimeImmutable();

        $form = $this->createForm(MilestoneReportFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $milestone = (new MilestoneReport())
                ->setProject($project)
                ->setTitle((string) $dto->title)
                ->setContent((string) $dto->content)
                ->setMilestoneDate($dto->milestoneDate ?? new \DateTimeImmutable())
                ->setCreatedBy($this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException());

            $this->entityManager->persist($milestone);
            $this->entityManager->flush();
            $this->activityLogger->log('milestone.created', $milestone, $this->getUser());

            $this->addFlash('success', 'Rapport de jalon créé.');

            return $this->redirectToRoute('app_milestone_index', ['projectId' => $projectId]);
        }

        return $this->render('milestone_report/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_milestone_edit', methods: ['GET', 'POST'])]
    public function edit(int $projectId, MilestoneReport $milestone, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $this->assertBelongsToProject($milestone, $project);

        $dto = new MilestoneReportDto();
        $dto->title = $milestone->getTitle();
        $dto->content = $milestone->getContent();
        $dto->milestoneDate = $milestone->getMilestoneDate();

        $form = $this->createForm(MilestoneReportFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $milestone
                ->setTitle((string) $dto->title)
                ->setContent((string) $dto->content)
                ->setMilestoneDate($dto->milestoneDate ?? $milestone->getMilestoneDate());

            $this->entityManager->flush();
            $this->activityLogger->log('milestone.updated', $milestone, $this->getUser());

            $this->addFlash('success', 'Rapport de jalon mis à jour.');

            return $this->redirectToRoute('app_milestone_index', ['projectId' => $projectId]);
        }

        return $this->render('milestone_report/edit.html.twig', [
            'project' => $project,
            'milestone' => $milestone,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_milestone_delete', methods: ['POST'])]
    public function delete(int $projectId, MilestoneReport $milestone, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $this->assertBelongsToProject($milestone, $project);

        if (!$this->isCsrfTokenValid('delete-milestone-' . $milestone->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($milestone);
        $this->entityManager->flush();

        $this->addFlash('success', 'Rapport de jalon supprimé.');

        return $this->redirectToRoute('app_milestone_index', ['projectId' => $projectId]);
    }

    private function getProject(int $projectId): Project
    {
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        return $project;
    }

    private function assertBelongsToProject(MilestoneReport $milestone, Project $project): void
    {
        if ($milestone->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
