<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Client;
use App\Domain\Entity\User;
use App\DTO\ClientDto;
use App\Form\ClientFormType;
use App\Repository\ClientRepository;
use App\Service\ActivityLogger;
use App\Service\CsvExporter;
use App\Service\ListFilterResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients')]
final class ClientController extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
        private readonly CsvExporter $csvExporter,
        private readonly ListFilterResolver $filters,
    ) {
    }

    #[Route('', name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');

        return $this->render('client/index.html.twig', [
            'clients' => $this->clientRepository->findFiltered($q),
            'filters' => ['q' => $q ?? ''],
        ]);
    }

    #[Route('/export.csv', name: 'app_client_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $q = $this->filters->string($request, 'q');
        $rows = [];
        foreach ($this->clientRepository->findFiltered($q) as $client) {
            $rows[] = [
                $client->getName(),
                $client->getCompany(),
                $client->getEmail(),
                $client->getPhone(),
                $client->getAddress(),
            ];
        }

        return $this->csvExporter->export('clients.csv', ['Nom', 'Entreprise', 'E-mail', 'Téléphone', 'Adresse'], $rows);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new ClientDto();
        $form = $this->createForm(ClientFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client = $this->mapDtoToEntity(new Client(), $dto);
            $client->setCreatedBy($this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException());

            $this->entityManager->persist($client);
            $this->entityManager->flush();
            $this->activityLogger->log('client.created', $client, $this->getUser());

            $this->addFlash('success', 'Client créé.');

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Client $client, Request $request): Response
    {
        $dto = $this->mapEntityToDto($client);
        $form = $this->createForm(ClientFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mapDtoToEntity($client, $dto);
            $this->entityManager->flush();
            $this->activityLogger->log('client.updated', $client, $this->getUser());

            $this->addFlash('success', 'Client mis à jour.');

            return $this->redirectToRoute('app_client_show', ['id' => $client->getId()]);
        }

        return $this->render('client/edit.html.twig', [
            'form' => $form,
            'client' => $client,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Client $client, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-client-' . $client->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($client);
        $this->entityManager->flush();

        $this->addFlash('success', 'Client supprimé.');

        return $this->redirectToRoute('app_client_index');
    }

    private function mapEntityToDto(Client $client): ClientDto
    {
        $dto = new ClientDto();
        $dto->name = $client->getName();
        $dto->email = $client->getEmail();
        $dto->phone = $client->getPhone();
        $dto->company = $client->getCompany();
        $dto->address = $client->getAddress();

        return $dto;
    }

    private function mapDtoToEntity(Client $client, ClientDto $dto): Client
    {
        return $client
            ->setName((string) $dto->name)
            ->setEmail($dto->email)
            ->setPhone($dto->phone)
            ->setCompany($dto->company)
            ->setAddress($dto->address);
    }
}
