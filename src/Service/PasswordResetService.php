<?php

// src/Service/PasswordResetService.php
namespace App\Service;

use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function generateToken(Employe $employe): string
    {
        $token = bin2hex(random_bytes(32));
        $employe->setResetToken($token);
        $employe->setTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        
        $this->em->flush();
        
        return $token;
    }

    public function getEmployeByToken(string $token): ?Employe
    {
        return $this->em->getRepository(Employe::class)->findOneBy([
            'resetToken' => $token,
            'tokenExpiresAt' => ['>' => new \DateTime()]
        ]);
    }

    public function updatePassword(Employe $employe, string $newPassword): void
    {
        $employe->setPassword(
            $this->passwordHasher->hashPassword($employe, $newPassword)
        );
        $employe->setResetToken(null);
        $employe->setTokenExpiresAt(null);
        
        $this->em->flush();
    }
}