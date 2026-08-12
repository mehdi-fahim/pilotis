<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\User;
use App\DTO\UserDto;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\CsvExporter;
use App\Service\ListFilterResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ActivityLogger $activityLogger,
        private readonly CsvExporter $csvExporter,
        private readonly ListFilterResolver $filters,
    ) {
    }

    #[Route('', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');
        $status = $this->filters->string($request, 'status');
        $activeOnly = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        return $this->render('admin/user/index.html.twig', [
            'users' => $this->userRepository->findFiltered($q, $activeOnly),
            'filters' => ['q' => $q ?? '', 'status' => $status ?? ''],
        ]);
    }

    #[Route('/export.csv', name: 'app_admin_user_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');
        $status = $this->filters->string($request, 'status');
        $activeOnly = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $rows = [];
        foreach ($this->userRepository->findFiltered($q, $activeOnly) as $user) {
            $rows[] = [
                $user->getFullName(),
                $user->getEmail(),
                implode(', ', $user->getRoles()),
                $user->isActive() ? 'Actif' : 'Inactif',
                $user->isVerified() ? 'Oui' : 'Non',
            ];
        }

        return $this->csvExporter->export('utilisateurs.csv', ['Nom', 'E-mail', 'Rôles', 'Statut', 'Vérifié'], $rows);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new UserDto();
        $form = $this->createForm(UserFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->mapDtoToEntity(new User(), $dto, true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->activityLogger->log('user.created', $user, $this->getUser());

            $this->addFlash('success', 'Utilisateur créé.');

            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response
    {
        $dto = $this->mapEntityToDto($user);
        $form = $this->createForm(UserFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mapDtoToEntity($user, $dto, false);
            $this->entityManager->flush();
            $this->activityLogger->log('user.updated', $user, $this->getUser());

            $this->addFlash('success', 'Utilisateur mis à jour.');

            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-user-' . $user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé.');

        return $this->redirectToRoute('app_admin_user_index');
    }

    private function mapEntityToDto(User $user): UserDto
    {
        $dto = new UserDto();
        $dto->email = $user->getEmail();
        $dto->firstName = $user->getFirstName();
        $dto->lastName = $user->getLastName();
        $dto->roles = array_values(array_filter(
            $user->getRoles(),
            static fn (string $role): bool => $role !== 'ROLE_USER'
        ));
        if ($dto->roles === []) {
            $dto->roles = ['ROLE_USER'];
        }
        $dto->isVerified = $user->isVerified();
        $dto->isActive = $user->isActive();

        return $dto;
    }

    private function mapDtoToEntity(User $user, UserDto $dto, bool $isNew): User
    {
        $user
            ->setEmail((string) $dto->email)
            ->setFirstName((string) $dto->firstName)
            ->setLastName((string) $dto->lastName)
            ->setRoles($dto->roles)
            ->setIsVerified($dto->isVerified)
            ->setIsActive($dto->isActive);

        if ($isNew || ($dto->password !== null && $dto->password !== '')) {
            $user->setPassword($this->passwordHasher->hashPassword($user, (string) $dto->password));
        }

        return $user;
    }
}
