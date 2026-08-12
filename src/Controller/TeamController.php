<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Team;
use App\Domain\Entity\TeamMember;
use App\Domain\Entity\User;
use App\DTO\TeamDto;
use App\Form\TeamFormType;
use App\Form\TeamMemberFormType;
use App\Repository\TeamRepository;
use App\Service\ActivityLogger;
use App\Service\CsvExporter;
use App\Service\ListFilterResolver;
use App\Service\TeamService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teams')]
final class TeamController extends AbstractController
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly TeamService $teamService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
        private readonly CsvExporter $csvExporter,
        private readonly ListFilterResolver $filters,
    ) {
    }

    #[Route('', name: 'app_team_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');

        return $this->render('team/index.html.twig', [
            'teams' => $this->teamRepository->findFiltered($q),
            'filters' => ['q' => $q ?? ''],
        ]);
    }

    #[Route('/export.csv', name: 'app_team_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');
        $rows = [];
        foreach ($this->teamRepository->findFiltered($q) as $team) {
            $rows[] = [
                $team->getName(),
                $team->getOwner()->getFullName(),
                $team->getMembers()->count(),
            ];
        }

        return $this->csvExporter->export('equipes.csv', ['Nom', 'Propriétaire', 'Membres'], $rows);
    }

    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new TeamDto();
        $dto->owner = $this->getUser() instanceof User ? $this->getUser() : null;

        $form = $this->createForm(TeamFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team = $this->teamService->createTeam(
                (string) $dto->name,
                $dto->owner ?? throw $this->createAccessDeniedException(),
                $dto->description,
            );

            $this->addFlash('success', 'Équipe créée.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    public function edit(Team $team, Request $request): Response
    {
        $dto = new TeamDto();
        $dto->name = $team->getName();
        $dto->description = $team->getDescription();
        $dto->owner = $team->getOwner();

        $form = $this->createForm(TeamFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team
                ->setName((string) $dto->name)
                ->setDescription($dto->description)
                ->setOwner($dto->owner ?? $team->getOwner());

            $this->entityManager->flush();
            $this->activityLogger->log('team.updated', $team, $this->getUser());

            $this->addFlash('success', 'Équipe mise à jour.');

            return $this->redirectToRoute('app_team_show', ['id' => $team->getId()]);
        }

        return $this->render('team/edit.html.twig', [
            'form' => $form,
            'team' => $team,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_team_delete', methods: ['POST'])]
    public function delete(Team $team, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-team-' . $team->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($team);
        $this->entityManager->flush();

        $this->addFlash('success', 'Équipe supprimée.');

        return $this->redirectToRoute('app_team_index');
    }

    #[Route('/{id}/members', name: 'app_team_members', methods: ['GET', 'POST'])]
    public function members(Team $team, Request $request): Response
    {
        $member = new TeamMember();
        $member->setTeam($team);

        $form = $this->createForm(TeamMemberFormType::class, $member);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->teamService->addMember($team, $member->getUser(), $member->getRole());
                $this->addFlash('success', 'Membre ajouté.');
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }

            return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
        }

        return $this->render('team/members.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/members/{userId}/remove', name: 'app_team_member_remove', methods: ['POST'])]
    public function removeMember(Team $team, int $userId, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('remove-member-' . $userId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if ($user === null) {
            throw $this->createNotFoundException();
        }

        try {
            $this->teamService->removeMember($team, $user);
            $this->addFlash('success', 'Membre retiré.');
        } catch (\InvalidArgumentException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
    }
}
