<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Decision;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\DTO\DecisionDto;
use App\Form\DecisionFormType;
use App\Repository\DecisionRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{projectId}/decisions')]
final class DecisionController extends AbstractController
{
    public function __construct(
        private readonly DecisionRepository $decisionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('', name: 'app_decision_index', methods: ['GET'])]
    public function index(int $projectId): Response
    {
        $project = $this->getProject($projectId);

        return $this->render('decision/index.html.twig', [
            'project' => $project,
            'decisions' => $this->decisionRepository->findBy(['project' => $project], ['meetingDate' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_decision_new', methods: ['GET', 'POST'])]
    public function new(int $projectId, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $dto = new DecisionDto();
        $dto->meetingDate = new \DateTimeImmutable();

        $form = $this->createForm(DecisionFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $decision = (new Decision())
                ->setProject($project)
                ->setTitle((string) $dto->title)
                ->setDescription((string) $dto->description)
                ->setMeetingDate($dto->meetingDate ?? new \DateTimeImmutable())
                ->setCreatedBy($this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException());

            $this->entityManager->persist($decision);
            $this->entityManager->flush();
            $this->activityLogger->log('decision.created', $decision, $this->getUser());

            $this->addFlash('success', 'Décision enregistrée.');

            return $this->redirectToRoute('app_decision_index', ['projectId' => $projectId]);
        }

        return $this->render('decision/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_decision_edit', methods: ['GET', 'POST'])]
    public function edit(int $projectId, Decision $decision, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $this->assertBelongsToProject($decision, $project);

        $dto = new DecisionDto();
        $dto->title = $decision->getTitle();
        $dto->description = $decision->getDescription();
        $dto->meetingDate = $decision->getMeetingDate();

        $form = $this->createForm(DecisionFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $decision
                ->setTitle((string) $dto->title)
                ->setDescription((string) $dto->description)
                ->setMeetingDate($dto->meetingDate ?? $decision->getMeetingDate());

            $this->entityManager->flush();
            $this->activityLogger->log('decision.updated', $decision, $this->getUser());

            $this->addFlash('success', 'Décision mise à jour.');

            return $this->redirectToRoute('app_decision_index', ['projectId' => $projectId]);
        }

        return $this->render('decision/edit.html.twig', [
            'project' => $project,
            'decision' => $decision,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_decision_delete', methods: ['POST'])]
    public function delete(int $projectId, Decision $decision, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $this->assertBelongsToProject($decision, $project);

        if (!$this->isCsrfTokenValid('delete-decision-' . $decision->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($decision);
        $this->entityManager->flush();

        $this->addFlash('success', 'Décision supprimée.');

        return $this->redirectToRoute('app_decision_index', ['projectId' => $projectId]);
    }

    private function getProject(int $projectId): Project
    {
        $project = $this->entityManager->getRepository(Project::class)->find($projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        return $project;
    }

    private function assertBelongsToProject(Decision $decision, Project $project): void
    {
        if ($decision->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
