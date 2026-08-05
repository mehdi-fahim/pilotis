<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RegistrationDto;
use App\Form\RegistrationFormType;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $dto = new RegistrationDto();
        $form = $this->createForm(RegistrationFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->authService->register(
                    (string) $dto->email,
                    (string) $dto->password,
                    (string) $dto->firstName,
                    (string) $dto->lastName,
                );
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('error', $exception->getMessage());

                return $this->redirectToRoute('app_register');
            }

            $this->addFlash('success', 'Compte créé. Vérifiez votre e-mail pour activer votre compte.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token): Response
    {
        if ($this->authService->verifyEmail($token)) {
            $this->addFlash('success', 'Votre adresse e-mail a été vérifiée. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('error', 'Le lien de vérification est invalide ou expiré.');

        return $this->render('registration/verify_email.html.twig');
    }
}
