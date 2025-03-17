<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Repository\TenantRepository;
use Symfony\Component\Console\Exception\InvalidArgumentException;

use function Symfony\Component\String\u;

/**
 * This class is used to provide an example of integrating simple classes as
 * services into a Symfony application.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CommandInputValidator
{
    final public const array ALLOWED_USER_ROLES = ['editor', 'admin'];

    public function __construct(
        private readonly TenantRepository $tenantRepository,
    ) {}

    public function validateUsername(string $username): string
    {
        if (empty($username)) {
            throw new InvalidArgumentException('The username can not be empty.');
        }

        if (1 !== preg_match('/^[a-z_]+$/', $username)) {
            throw new InvalidArgumentException('The username must contain only lowercase latin characters and underscores.');
        }

        return $username;
    }

    public function validatePassword(string $plainPassword): string
    {
        if (empty($plainPassword)) {
            throw new InvalidArgumentException('The password can not be empty.');
        }

        if (u($plainPassword)->trim()->length() < 6) {
            throw new InvalidArgumentException('The password must be at least 6 characters long.');
        }

        return $plainPassword;
    }

    public function validateTenantKey(string $plainPassword): string
    {
        if (empty($plainPassword)) {
            throw new InvalidArgumentException('The tenant key can not be empty.');
        }

        if (u($plainPassword)->trim()->length() < 3) {
            throw new InvalidArgumentException('The tenant key must be at least 3 characters long.');
        }

        return $plainPassword;
    }

    public function validateEmail(string $email): string
    {
        if (empty($email)) {
            throw new InvalidArgumentException('The email can not be empty.');
        }

        if (null === u($email)->indexOf('@')) {
            throw new InvalidArgumentException('The email should look like a real email.');
        }

        return $email;
    }

    public function validateFullName(string $fullName): string
    {
        if (empty($fullName)) {
            throw new InvalidArgumentException('The full name can not be empty.');
        }

        return $fullName;
    }

    public function validateRole(string $role): string
    {
        if (empty($role)) {
            throw new InvalidArgumentException('The role can not be empty.');
        }

        if (!in_array($role, self::ALLOWED_USER_ROLES)) {
            throw new InvalidArgumentException('Unknown role: '.$role);
        }

        return $role;
    }

    public function validateTenantKeys(array $tenantKeys): array
    {
        if (empty($tenantKeys)) {
            throw new InvalidArgumentException('The user must belong to at least one tenant.');
        }

        $unknownKeys = [];
        foreach ($tenantKeys as $tenantKey) {
            $tenant = $this->tenantRepository->findOneBy(['tenantKey' => $tenantKey]);
            if (null === $tenant) {
                $unknownKeys[] = $tenantKey;
            }
        }

        if (0 !== count($unknownKeys)) {
            throw new InvalidArgumentException(sprintf('Unknown tenant keys: %s.', implode(', ', $unknownKeys)));
        }

        return $tenantKeys;
    }
}
