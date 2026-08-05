<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Comment;
use App\Domain\Entity\Task;
use App\Domain\Entity\User;
use App\DTO\CommentDto;
use App\Form\CommentFormType;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('/tasks/{id}/comments', name: 'app_comment_add', methods: ['POST'])]
    public function add(Task $task, Request $request): Response
    {
        $dto = new CommentDto();
        $form = $this->createForm(CommentFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = (new Comment())
                ->setTask($task)
                ->setAuthor($this->getUser() instanceof User ? $this->getUser() : throw $this->createAccessDeniedException())
                ->setContent((string) $dto->content);

            $task->addComment($comment);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            $this->activityLogger->log('comment.added', $comment, $this->getUser());

            $this->addFlash('success', 'Commentaire ajouté.');
        }

        return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
    }
}
