<?php

declare(strict_types=1);

namespace App\Service;

readonly class KeyVaultService
{
    // APP_KEY_VAULT_SOURCE (set in environment/.env) options:
    public const ENVIRONMENT = 'ENVIRONMENT';

    public function __construct(
        private string $keyVaultSource,
        private array $keyVaultArray,
    ) {}

    /**
     * Get the value for the given key.
     *
     * Returns null if the value is not found.
     */
    public function getValue(string $key): ?string
    {
        return match ($this->keyVaultSource) {
            self::ENVIRONMENT => $this->getValueFromEnvironment($key),
        };
    }

    private function getValueFromEnvironment(string $key): ?string
    {
        return $this->keyVaultArray[$key] ?? null;
    }
}
