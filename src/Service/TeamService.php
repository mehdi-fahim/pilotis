<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Team;
use App\Domain\Entity\TeamMember;
use App\Domain\Entity\User;
use App\Domain\Enum\TeamRole;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TeamService
{
    public function __construct(
        private readonly TeamMemberRepository $teamMemberRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function createTeam(string $name, User $owner, ?string $description = null): Team
    {
        $team = (new Team())
            ->setName($name)
            ->setDescription($description)
            ->setOwner($owner);

        $ownerMember = (new TeamMember())
            ->setUser($owner)
            ->setRole(TeamRole::OWNER);

        $team->addMember($ownerMember);

        $this->entityManager->persist($team);
        $this->entityManager->flush();

        $this->activityLogger->log('team.created', $team, $owner);

        return $team;
    }

    public function addMember(Team $team, User $user, TeamRole $role = TeamRole::MEMBER): TeamMember
    {
        if ($role === TeamRole::OWNER && $team->getOwner()->getId() !== $user->getId()) {
            throw new \InvalidArgumentException('Only the team owner can hold the owner role.');
        }

        $existingMember = $this->teamMemberRepository->findOneBy([
            'team' => $team,
            'user' => $user,
        ]);

        if ($existingMember !== null) {
            throw new \InvalidArgumentException(sprintf('User "%s" is already a member of this team.', $user->getEmail()));
        }

        $member = (new TeamMember())
            ->setTeam($team)
            ->setUser($user)
            ->setRole($role);

        $team->addMember($member);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->activityLogger->log('team.member_added', $team, $user, [
            'memberId' => $user->getId(),
            'role' => $role->value,
        ]);

        return $member;
    }

    public function removeMember(Team $team, User $user): void
    {
        if ($team->getOwner()->getId() === $user->getId()) {
            throw new \InvalidArgumentException('The team owner cannot be removed.');
        }

        $member = $this->teamMemberRepository->findOneBy([
            'team' => $team,
            'user' => $user,
        ]);

        if ($member === null) {
            throw new \InvalidArgumentException(sprintf('User "%s" is not a member of this team.', $user->getEmail()));
        }

        $team->removeMember($member);
        $this->entityManager->remove($member);
        $this->entityManager->flush();

        $this->activityLogger->log('team.member_removed', $team, $user, [
            'memberId' => $user->getId(),
        ]);
    }
}
