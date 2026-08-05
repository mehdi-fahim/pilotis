<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\EmailVerificationToken;
use App\Domain\Entity\PasswordResetToken;
use App\Domain\Entity\User;
use App\Repository\EmailVerificationTokenRepository;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailVerificationTokenRepository $emailVerificationTokenRepository,
        private readonly PasswordResetTokenRepository $passwordResetTokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function register(string $email, string $password, string $firstName, string $lastName): User
    {
        if ($this->userRepository->findOneByEmail($email) !== null) {
            throw new \InvalidArgumentException(sprintf('A user with email "%s" already exists.', $email));
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPassword($this->passwordHasher->hashPassword(new User(), $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->createEmailVerificationToken($user);

        return $user;
    }

    public function createEmailVerificationToken(User $user): EmailVerificationToken
    {
        $existingTokens = $this->emailVerificationTokenRepository->findBy(['user' => $user]);
        foreach ($existingTokens as $existingToken) {
            $this->entityManager->remove($existingToken);
        }

        $token = (new EmailVerificationToken())
            ->setUser($user)
            ->setToken(bin2hex(random_bytes(32)));

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->mailer->send(
            (new Email())
                ->to($user->getEmail())
                ->subject('Vérifiez votre adresse e-mail - Pilotis')
                ->text(sprintf(
                    "Bonjour %s,\n\nVeuillez vérifier votre adresse e-mail en utilisant le token suivant : %s\n\nCe lien expire dans 24 heures.",
                    $user->getFirstName(),
                    $token->getToken()
                ))
        );

        return $token;
    }

    public function verifyEmail(string $token): bool
    {
        $verificationToken = $this->emailVerificationTokenRepository->findValidToken($token);

        if ($verificationToken === null) {
            return false;
        }

        $user = $verificationToken->getUser();
        $user->setIsVerified(true);

        $this->entityManager->remove($verificationToken);
        $this->entityManager->flush();

        return true;
    }

    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findOneByEmail($email);

        if ($user === null) {
            return;
        }

        $existingTokens = $this->passwordResetTokenRepository->findBy(['user' => $user]);
        foreach ($existingTokens as $existingToken) {
            $this->entityManager->remove($existingToken);
        }

        $token = (new PasswordResetToken())
            ->setUser($user)
            ->setToken(bin2hex(random_bytes(32)));

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->mailer->send(
            (new Email())
                ->to($user->getEmail())
                ->subject('Réinitialisation de mot de passe - Pilotis')
                ->text(sprintf(
                    "Bonjour %s,\n\nUtilisez le token suivant pour réinitialiser votre mot de passe : %s\n\nCe lien expire dans 1 heure.",
                    $user->getFirstName(),
                    $token->getToken()
                ))
        );
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $resetToken = $this->passwordResetTokenRepository->findValidToken($token);

        if ($resetToken === null) {
            return false;
        }

        $user = $resetToken->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

        $this->entityManager->remove($resetToken);
        $this->entityManager->flush();

        return true;
    }
}
