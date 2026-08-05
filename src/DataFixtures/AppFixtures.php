<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Entity\Task;
use App\Domain\Entity\User;
use App\Domain\Enum\Priority;
use App\Domain\Enum\ProjectStatus;
use App\Domain\Enum\TaskStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@pilotis.local')
            ->setFirstName('Mehdi')
            ->setLastName('Pilotis')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $it = (new Department())->setName('Informatique')->setCode('IT')->setDescription('Service des systèmes d\'information');
        $rh = (new Department())->setName('Ressources Humaines')->setCode('RH');
        $finance = (new Department())->setName('Finance')->setCode('FIN');
        $manager->persist($it);
        $manager->persist($rh);
        $manager->persist($finance);

        $sponsor = (new Actor())
            ->setFirstName('Marie')
            ->setLastName('Dupont')
            ->setRole('Directrice RH')
            ->setDepartment($rh)
            ->setEmail('marie.dupont@entreprise.local');
        $consultant = (new Actor())
            ->setFirstName('Jean')
            ->setLastName('Martin')
            ->setRole('Consultant SAP')
            ->setDepartment($it);
        $manager->persist($sponsor);
        $manager->persist($consultant);

        $project = new Project();
        $project->setName('Migration ERP 2026')
            ->setDescription('Projet interne de migration vers un nouvel ERP')
            ->setStatus(ProjectStatus::ACTIVE)
            ->setPriority(Priority::HIGH)
            ->setStartDate(new \DateTimeImmutable('-15 days'))
            ->setEndDate(new \DateTimeImmutable('+120 days'));
        $manager->persist($project);

        $tasks = [
            ['Cartographie des processus', TaskStatus::DONE, $rh, $sponsor, -10, -2],
            ['Paramétrage module RH', TaskStatus::IN_PROGRESS, $it, $consultant, -5, 15],
            ['Formation utilisateurs', TaskStatus::TODO, $rh, $sponsor, 20, 35],
            ['Recette finance', TaskStatus::TODO, $finance, null, 30, 45],
        ];

        foreach ($tasks as [$title, $status, $department, $actor, $startOffset, $endOffset]) {
            $task = new Task();
            $task->setTitle($title)
                ->setProject($project)
                ->setDepartment($department)
                ->setAssignedActor($actor)
                ->setStatus($status)
                ->setPriority(Priority::MEDIUM)
                ->setEstimateMinutes(960)
                ->setStartDate(new \DateTimeImmutable(sprintf('%+d days', $startOffset)))
                ->setDueDate(new \DateTimeImmutable(sprintf('%+d days', $endOffset)))
                ->setKanbanOrder($this->nextKanbanOrder($manager, $project, $status));
            $manager->persist($task);
        }

        $manager->flush();
    }

    /** @var array<string, int> */
    private array $kanbanCounters = [];

    private function nextKanbanOrder(ObjectManager $manager, Project $project, TaskStatus $status): int
    {
        $key = $project->getName() . ':' . $status->value;
        $this->kanbanCounters[$key] ??= 0;
        $order = $this->kanbanCounters[$key];
        ++$this->kanbanCounters[$key];

        return $order;
    }
}
