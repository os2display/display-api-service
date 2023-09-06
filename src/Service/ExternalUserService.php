<?php

namespace App\Service;

use App\Entity\User;
use App\Exceptions\CodeGenerationException;
use App\Exceptions\ExternalUserCodeException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ExternalUserService
{

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws ExternalUserCodeException
     */
    public function activateExternalUser(User $user, string $code): void
    {
        // TODO: Extract user data unique field, and bind external user to current logged in user.

        $now = new \DateTime();

        if ($user->getDisabled() !== true) {
            throw new ExternalUserCodeException("User already activated.");
        }

        if ($now > $user->getExternalUserCodeExpire()) {
            throw new ExternalUserCodeException("Code has expired.");
        }

        if ($user->getExternalUserCode() !== $code) {
            throw new ExternalUserCodeException("Code is invalid.");
        }

        $user->setDisabled(false);
        $user->setExternalUserCode(null);
        $user->setExternalUserCodeExpire(null);

        $this->entityManager->flush();
    }

    /**
     * @throws CodeGenerationException
     */
    public function generateExternalUserCode(): string
    {
        $i = 0;

        do {
            $code = $this->generateRandomCode();

            $usersWithCode = $this->userRepository->findBy(['externalUserCode' => $code]);

            if (count($usersWithCode) === 0) {
                return $code;
            }

            $i++;
        } while ($i < 100);

        throw new CodeGenerationException("Could not generate unique code.");
    }

    private function generateRandomCode(): string
    {
        $length = 12;
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charsLength = strlen($chars);
        $bindKey = '';

        for ($i = 0; $i < $length; ++$i) {
            $bindKey .= $chars[rand(0, $charsLength - 1)];
        }

        return $bindKey;
    }
}
