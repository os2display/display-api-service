<?php

declare(strict_types=1);

namespace App\Service;

readonly class KeyVaultService
{
    // APP_KEY_VAULT_SOURCE (set in environment/.env) options:
    public const ENVIRONMENT = 'ENVIRONMENT';
    public const AZURE_KEY_VAULT = 'AZURE_KEY_VAULT';

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
            self::AZURE_KEY_VAULT => $this->getValueFromAzureKeyVault($key),
        };
    }

    private function getValueFromEnvironment(string $key): ?string
    {
        return $this->keyVaultArray[$key] ?? null;
    }

    private function getValueFromAzureKeyVault(string $key): ?string
    {
        // TODO: Add support for Azure KeyVault.
        // https://github.com/itk-dev/AzureKeyVaultPhp

        throw new \Exception('Not implemented');
    }
}
