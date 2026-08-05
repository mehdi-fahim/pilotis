<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Entity\EmailVerificationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailVerificationToken>
 */
class EmailVerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerificationToken::class);
    }

    public function findValidToken(string $token): ?EmailVerificationToken
    {
        $entity = $this->findOneBy(['token' => $token]);

        if ($entity === null || $entity->isExpired()) {
            return null;
        }

        return $entity;
    }
}
