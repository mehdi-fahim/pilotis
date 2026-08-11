<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Incident;
use App\Domain\Entity\IncidentDocument;
use App\Domain\Entity\User;
use App\DTO\CommentDto;
use App\DTO\IncidentDto;
use App\Form\CommentFormType;
use App\Form\DocumentFormType;
use App\Form\IncidentFormType;
use App\Repository\IncidentRepository;
use App\Service\ActivityLogger;
use App\Service\IncidentDocumentUploader;
use App\Service\IncidentSlaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/incidents')]
final class IncidentController extends AbstractController
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
        private readonly IncidentSlaService $incidentSlaService,
        private readonly IncidentDocumentUploader $incidentDocumentUploader,
    ) {
    }

    #[Route('', name: 'app_incident_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('incident/index.html.twig', [
            'incidents' => $this->incidentRepository->findBy([], ['openedAt' => 'DESC']),
            'openCount' => $this->incidentRepository->countOpen(),
        ]);
    }

    #[Route('/new', name: 'app_incident_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new IncidentDto();
        $dto->discoveredAt = new \DateTimeImmutable();

        $form = $this->createForm(IncidentFormType::class, $dto, ['is_create' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $incident = $this->mapDtoToEntity(new Incident(), $dto, true);
            $incident->setReportedBy($this->getUser() instanceof User ? $this->getUser() : null);
            $this->entityManager->persist($incident);
            $this->entityManager->flush();
            $this->activityLogger->log('incident.created', $incident, $this->getUser());

            $this->addFlash('success', sprintf(
                'Incident créé. Échéance SLA : %s.',
                $incident->getDueDate()?->format('d/m/Y') ?? '—'
            ));

            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        return $this->render('incident/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_incident_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Incident $incident): Response
    {
        $commentForm = $this->createForm(CommentFormType::class, new CommentDto(), [
            'action' => $this->generateUrl('app_incident_comment_add', ['id' => $incident->getId()]),
        ]);
        $documentForm = $this->createForm(DocumentFormType::class, null, [
            'action' => $this->generateUrl('app_incident_document_upload', ['id' => $incident->getId()]),
        ]);

        return $this->render('incident/show.html.twig', [
            'incident' => $incident,
            'commentForm' => $commentForm,
            'documentForm' => $documentForm,
            'slaDays' => $this->incidentSlaService->slaDays($incident->getPriority()),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_incident_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Incident $incident, Request $request): Response
    {
        $dto = $this->mapEntityToDto($incident);
        $form = $this->createForm(IncidentFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mapDtoToEntity($incident, $dto, false);
            $this->entityManager->flush();
            $this->activityLogger->log('incident.updated', $incident, $this->getUser());

            $this->addFlash('success', 'Incident mis à jour.');

            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        return $this->render('incident/edit.html.twig', [
            'form' => $form,
            'incident' => $incident,
        ]);
    }

    #[Route('/{id}/documents', name: 'app_incident_document_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadDocument(Incident $incident, Request $request): Response
    {
        $form = $this->createForm(DocumentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file !== null) {
                $document = $this->incidentDocumentUploader->upload(
                    $incident,
                    $this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException(),
                    $file,
                );
                $this->activityLogger->log('incident.document.uploaded', $document, $this->getUser());
                $this->addFlash('success', 'Document téléversé.');
            }
        }

        return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
    }

    #[Route('/documents/{id}/download', name: 'app_incident_document_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadDocument(IncidentDocument $document): Response
    {
        $filePath = $this->incidentDocumentUploader->getFilePath($document);

        if (!is_file($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalFilename(),
        );

        return $response;
    }

    #[Route('/documents/{id}/delete', name: 'app_incident_document_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteDocument(IncidentDocument $document, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-incident-document-' . $document->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $incidentId = $document->getIncident()->getId();
        $this->incidentDocumentUploader->remove($document);
        $this->activityLogger->log('incident.document.deleted', $document, $this->getUser());

        $this->addFlash('success', 'Document supprimé.');

        return $this->redirectToRoute('app_incident_show', ['id' => $incidentId]);
    }

    #[Route('/{id}/delete', name: 'app_incident_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Incident $incident, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-incident-' . $incident->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($incident);
        $this->entityManager->flush();

        $this->addFlash('success', 'Incident supprimé.');

        return $this->redirectToRoute('app_incident_index');
    }

    private function mapEntityToDto(Incident $incident): IncidentDto
    {
        $dto = new IncidentDto();
        $dto->title = $incident->getTitle();
        $dto->description = $incident->getDescription();
        $dto->status = $incident->getStatus();
        $dto->priority = $incident->getPriority();
        $dto->department = $incident->getDepartment();
        $dto->assignedActor = $incident->getAssignedActor();
        $dto->discoveredAt = $incident->getDiscoveredAt();
        $dto->solution = $incident->getSolution();
        $dto->reproductionSteps = $incident->getReproductionSteps();
        $dto->impact = $incident->getImpact();
        $dto->environment = $incident->getEnvironment();
        $dto->rootCause = $incident->getRootCause();
        $dto->dueDate = $incident->getDueDate();

        return $dto;
    }

    private function mapDtoToEntity(Incident $incident, IncidentDto $dto, bool $isCreate): Incident
    {
        $discoveredAt = $dto->discoveredAt ?? new \DateTimeImmutable();

        $incident
            ->setTitle((string) $dto->title)
            ->setDescription($dto->description)
            ->setPriority($dto->priority)
            ->setDepartment($dto->department)
            ->setAssignedActor($dto->assignedActor)
            ->setDiscoveredAt($discoveredAt)
            ->setReproductionSteps($dto->reproductionSteps)
            ->setImpact($dto->impact)
            ->setEnvironment($dto->environment);

        if (!$isCreate) {
            $incident
                ->setStatus($dto->status)
                ->setSolution($dto->solution)
                ->setRootCause($dto->rootCause);
        }

        if ($dto->dueDate !== null) {
            $incident->setDueDate($dto->dueDate);
        } elseif ($isCreate || $incident->getDueDate() === null) {
            $incident->setDueDate($this->incidentSlaService->computeDueDate($dto->priority, $discoveredAt));
        }

        return $incident;
    }
}
