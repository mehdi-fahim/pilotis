<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Project;
use App\Domain\Entity\ProjectMeeting;
use App\Domain\Entity\User;
use App\Repository\ProjectMeetingRepository;
use App\Repository\ProjectRepository;
use App\Service\MeetingHtmlSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects/{projectId}/meetings', requirements: ['projectId' => '\d+'])]
final class ProjectMeetingController extends AbstractController
{
    private const MAX_CONTENT_BYTES = 200000;

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectMeetingRepository $projectMeetingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MeetingHtmlSanitizer $htmlSanitizer,
    ) {
    }

    #[Route('', name: 'app_project_meeting_new', methods: ['POST'])]
    public function create(int $projectId, Request $request): Response
    {
        $project = $this->getProject($projectId);
        if (!$this->isCsrfTokenValid('new-meeting-' . $project->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $today = new \DateTimeImmutable('today');
        $meeting = (new ProjectMeeting())
            ->setProject($project)
            ->setAuthor($this->requireUser())
            ->setTitle('Réunion du ' . $today->format('d/m/Y'))
            ->setHeldAt($today)
            ->setContent($this->htmlSanitizer->sanitize($this->defaultContent()));

        $this->entityManager->persist($meeting);
        $this->entityManager->flush();

        $this->addFlash('success', 'Réunion ajoutée.');

        return $this->redirectToRoute('app_project_show', [
            'id' => $project->getId(),
            'tab' => 'meetings',
            'note' => $meeting->getId(),
        ]);
    }

    #[Route('/{meetingId}', name: 'app_project_meeting_update', methods: ['POST'], requirements: ['meetingId' => '\d+'])]
    public function update(int $projectId, int $meetingId, Request $request): JsonResponse
    {
        $project = $this->getProject($projectId);
        $meeting = $this->getMeeting($project, $meetingId);

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $token = $payload['_token'] ?? '';
        if (!is_string($token) || !$this->isCsrfTokenValid('save-meeting-' . $meeting->getId(), $token)) {
            return $this->json(['error' => 'Jeton de sécurité invalide. Rechargez la page.'], Response::HTTP_FORBIDDEN);
        }

        $content = $payload['content'] ?? '';
        if (!is_string($content)) {
            return $this->json(['error' => 'Contenu invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($content) > self::MAX_CONTENT_BYTES) {
            return $this->json(['error' => 'Le compte rendu est trop long.'], Response::HTTP_BAD_REQUEST);
        }

        $heldAt = $this->parseDate($payload['heldAt'] ?? null);
        if ($heldAt === null) {
            return $this->json(['error' => 'Date de réunion invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $meeting
            ->setTitle($this->normalizeTitle($payload['title'] ?? null))
            ->setHeldAt($heldAt)
            ->setContent($this->htmlSanitizer->sanitize($content));

        $this->entityManager->flush();

        return $this->json([
            'ok' => true,
            'title' => $meeting->getTitle(),
            'heldAt' => $meeting->getHeldAt()->format('Y-m-d'),
        ]);
    }

    #[Route('/{meetingId}/delete', name: 'app_project_meeting_delete', methods: ['POST'], requirements: ['meetingId' => '\d+'])]
    public function delete(int $projectId, int $meetingId, Request $request): Response
    {
        $project = $this->getProject($projectId);
        $meeting = $this->getMeeting($project, $meetingId);

        if (!$this->isCsrfTokenValid('delete-meeting-' . $meeting->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($meeting);
        $this->entityManager->flush();

        $this->addFlash('success', 'Réunion supprimée.');

        return $this->redirectToRoute('app_project_show', [
            'id' => $project->getId(),
            'tab' => 'meetings',
        ]);
    }

    private function getProject(int $projectId): Project
    {
        $project = $this->projectRepository->find($projectId);
        if (!$project instanceof Project) {
            throw $this->createNotFoundException();
        }

        return $project;
    }

    private function getMeeting(Project $project, int $meetingId): ProjectMeeting
    {
        $meeting = $this->projectMeetingRepository->findOneForProject($project, $meetingId);
        if (!$meeting instanceof ProjectMeeting) {
            throw $this->createNotFoundException();
        }

        return $meeting;
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function normalizeTitle(mixed $title): string
    {
        if (!is_string($title)) {
            return 'Sans titre';
        }

        $title = trim(strip_tags($title));
        $normalized = preg_replace('/\s+/u', ' ', $title);
        $title = trim(is_string($normalized) ? $normalized : $title);

        if ($title === '') {
            return 'Sans titre';
        }

        if (mb_strlen($title) > 200) {
            return mb_substr($title, 0, 200);
        }

        return $title;
    }

    private function parseDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date instanceof \DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }

    private function defaultContent(): string
    {
        return <<<'HTML'
<p><strong>Participants</strong></p>
<p><br></p>
<p><strong>Ordre du jour</strong></p>
<ul><li><br></li></ul>
<p><strong>Échanges</strong></p>
<p><br></p>
<p><strong>Décisions</strong></p>
<ul><li><br></li></ul>
<p><strong>Actions</strong></p>
<ul><li><br></li></ul>
HTML;
    }
}
