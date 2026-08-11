<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Document;
use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Form\DocumentFormType;
use App\Repository\DocumentRepository;
use App\Repository\ProjectRepository;
use App\Service\ActivityLogger;
use App\Service\DocumentUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly DocumentUploader $documentUploader,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('/documents', name: 'app_document_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->addFlash('info', 'Les documents se consultent depuis un projet.');

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/projects/{projectId}/documents', name: 'app_document_project', methods: ['GET', 'POST'], requirements: ['projectId' => '\d+'])]
    public function projectDocuments(int $projectId, Request $request): Response
    {
        $project = $this->projectRepository->find($projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(DocumentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file !== null) {
                $document = $this->documentUploader->upload(
                    $project,
                    $this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException(),
                    $file,
                );
                $this->activityLogger->log('document.uploaded', $document, $this->getUser());
                $this->addFlash('success', 'Document téléversé.');
            }

            return $this->redirectToRoute('app_document_project', ['projectId' => $projectId]);
        }

        return $this->render('document/project.html.twig', [
            'project' => $project,
            'documents' => $this->documentRepository->findBy(['project' => $project], ['createdAt' => 'DESC']),
            'form' => $form,
        ]);
    }

    #[Route('/documents/{id}/download', name: 'app_document_download', methods: ['GET'])]
    public function download(Document $document): Response
    {
        $filePath = $this->documentUploader->getFilePath($document);

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

    #[Route('/documents/{id}/delete', name: 'app_document_delete', methods: ['POST'])]
    public function delete(Document $document, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-document-' . $document->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $projectId = $document->getProject()->getId();
        $this->documentUploader->remove($document);
        $this->activityLogger->log('document.deleted', $document, $this->getUser());

        $this->addFlash('success', 'Document supprimé.');

        if ($projectId !== null) {
            return $this->redirectToRoute('app_document_project', ['projectId' => $projectId]);
        }

        return $this->redirectToRoute('app_document_index');
    }
}
