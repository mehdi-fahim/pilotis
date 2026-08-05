<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\PasswordResetDto;
use App\DTO\PasswordResetRequestDto;
use App\Form\PasswordResetFormType;
use App\Form\PasswordResetRequestFormType;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    #[Route('/reset-password', name: 'app_password_reset_request')]
    public function request(Request $request): Response
    {
        $dto = new PasswordResetRequestDto();
        $form = $this->createForm(PasswordResetRequestFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->authService->requestPasswordReset((string) $dto->email);
            $this->addFlash('success', 'Si un compte existe, un e-mail de réinitialisation a été envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('password_reset/request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_password_reset')]
    public function reset(string $token, Request $request): Response
    {
        $dto = new PasswordResetDto();
        $form = $this->createForm(PasswordResetFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->authService->resetPassword($token, (string) $dto->password)) {
                $this->addFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
        }

        return $this->render('password_reset/reset.html.twig', [
            'form' => $form,
            'token' => $token,
        ]);
    }
}
