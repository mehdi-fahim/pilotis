<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\DTO\ActorDto;
use App\Form\ActorFormType;
use App\Repository\ActorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/actors')]
final class ActorController extends AbstractController
{
    public function __construct(
        private readonly ActorRepository $actorRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_actor_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('actor/index.html.twig', [
            'actors' => $this->actorRepository->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_actor_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new ActorDto();
        $form = $this->createForm(ActorFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $actor = $this->mapDtoToEntity(new Actor(), $dto);
            $this->entityManager->persist($actor);
            $this->entityManager->flush();

            $this->addFlash('success', 'Acteur créé.');

            return $this->redirectToRoute('app_actor_show', ['id' => $actor->getId()]);
        }

        return $this->render('actor/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_actor_show', methods: ['GET'])]
    public function show(Actor $actor): Response
    {
        return $this->render('actor/show.html.twig', [
            'actor' => $actor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_actor_edit', methods: ['GET', 'POST'])]
    public function edit(Actor $actor, Request $request): Response
    {
        $dto = $this->mapEntityToDto($actor);
        $form = $this->createForm(ActorFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mapDtoToEntity($actor, $dto);
            $this->entityManager->flush();

            $this->addFlash('success', 'Acteur mis à jour.');

            return $this->redirectToRoute('app_actor_show', ['id' => $actor->getId()]);
        }

        return $this->render('actor/edit.html.twig', [
            'form' => $form,
            'actor' => $actor,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_actor_delete', methods: ['POST'])]
    public function delete(Actor $actor, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-actor-' . $actor->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($actor);
        $this->entityManager->flush();

        $this->addFlash('success', 'Acteur supprimé.');

        return $this->redirectToRoute('app_actor_index');
    }

    private function mapEntityToDto(Actor $actor): ActorDto
    {
        $dto = new ActorDto();
        $dto->firstName = $actor->getFirstName();
        $dto->lastName = $actor->getLastName();
        $dto->email = $actor->getEmail();
        $dto->phone = $actor->getPhone();
        $dto->role = $actor->getRole();
        $dto->department = $actor->getDepartment();
        $dto->notes = $actor->getNotes();

        return $dto;
    }

    private function mapDtoToEntity(Actor $actor, ActorDto $dto): Actor
    {
        return $actor
            ->setFirstName((string) $dto->firstName)
            ->setLastName((string) $dto->lastName)
            ->setEmail($dto->email)
            ->setPhone($dto->phone)
            ->setRole($dto->role)
            ->setDepartment($dto->department)
            ->setNotes($dto->notes);
    }
}
