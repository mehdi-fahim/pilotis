<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Incident;
use App\Domain\Entity\IncidentComment;
use App\Domain\Entity\User;
use App\DTO\CommentDto;
use App\Form\CommentFormType;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IncidentCommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('/incidents/{id}/comments', name: 'app_incident_comment_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function add(Incident $incident, Request $request): Response
    {
        $dto = new CommentDto();
        $form = $this->createForm(CommentFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = (new IncidentComment())
                ->setIncident($incident)
                ->setAuthor($this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException())
                ->setContent((string) $dto->content);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            $this->activityLogger->log('incident.comment.added', $comment, $this->getUser());

            $this->addFlash('success', 'Commentaire ajouté.');
        }

        return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
    }
}
