<?php

namespace App\Service;

use App\Entity\ExternalUserActivationCode;
use App\Exceptions\CodeGenerationException;
use App\Repository\ExternalUserActivationCodeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ExternalUserService
{
    public function __construct(
        private readonly ExternalUserActivationCodeRepository $activationCodeRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @throws CodeGenerationException
     */
    public function refreshCode(ExternalUserActivationCode $code): ExternalUserActivationCode
    {
        $code->setCode($this->generateExternalUserCode());
        $code->setCodeExpire((new \DateTime())->add(new \DateInterval('P2D')));
        $this->entityManager->flush();

        return $code;
    }

    public function activateExternalUser(string $code): void
    {
        // Get user data from session.
        // Create / Retrieve user.
        // Update tenants/roles for user.

        // throw new ExternalUserCodeException

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

            $usersWithCode = $this->activationCodeRepository->findBy(['code' => $code]);

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
