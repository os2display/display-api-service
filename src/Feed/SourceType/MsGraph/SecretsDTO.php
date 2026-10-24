<?php

declare(strict_types=1);

namespace App\Feed\SourceType\MsGraph;

use App\Entity\Tenant\FeedSource;

/**
 * Credentials for an Entra ID (Azure AD) app registration with application
 * (app-only) permissions for Microsoft Graph.
 *
 * "App ID" and "Client ID" are the same value — the Azure portal labels it
 * "Application (client) ID". Only three values are needed: the directory
 * (tenant) id, the application (client) id and a client secret *value* (the
 * secret itself, not the secret id shown next to it in the portal).
 */
readonly class SecretsDTO
{
    public string $tenantId;
    public string $clientId;
    public string $clientSecret;

    /**
     * Selected room lists (locations) to pick rooms from. Empty means "all rooms".
     *
     * @var list<string>
     */
    public array $locations;

    public function __construct(FeedSource $feedSource)
    {
        $secrets = $feedSource->getSecrets();

        if (null === $secrets) {
            throw new \RuntimeException('No secrets found for feed source.');
        }

        if (!isset($secrets['tenant_id'], $secrets['client_id'], $secrets['client_secret'])) {
            throw new \RuntimeException('Missing required secrets for feed source. Expected tenant_id, client_id and client_secret.');
        }

        $tenantId = trim((string) $secrets['tenant_id']);
        $clientId = trim((string) $secrets['client_id']);
        $clientSecret = (string) $secrets['client_secret'];

        // The tenant id and client id are interpolated into the token URL and
        // request body, so reject anything that is not a GUID (or, for the
        // tenant, one of the verified domain names that Entra ID also accepts).
        if (1 !== preg_match('/^[A-Za-z0-9._-]+$/', $tenantId)) {
            throw new \RuntimeException('Invalid tenant_id. Expected a GUID or a domain name.');
        }

        if (1 !== preg_match('/^[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}$/', $clientId)) {
            throw new \RuntimeException('Invalid client_id. Expected a GUID.');
        }

        if ('' === $clientSecret) {
            throw new \RuntimeException('Invalid client_secret. Must not be empty.');
        }

        $this->tenantId = $tenantId;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $locations = $secrets['locations'] ?? [];
        $this->locations = is_array($locations)
            ? array_values(array_filter($locations, static fn (mixed $location): bool => is_string($location) && '' !== $location))
            : [];
    }

    /**
     * Stable, non-reversible fingerprint of the credentials.
     *
     * Used as part of cache keys so rotating a secret invalidates the cached
     * access token instead of serving one minted with the old credentials.
     */
    public function getCredentialsHash(): string
    {
        return substr(hash('sha256', $this->tenantId.'|'.$this->clientId.'|'.$this->clientSecret), 0, 16);
    }
}
