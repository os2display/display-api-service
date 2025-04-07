<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Colibo;

use App\Entity\Tenant\FeedSource;

readonly class SecretsDTO
{
    public string $apiBaseUri;
    public string $clientId;
    public string $clientSecret;

    public function __construct(FeedSource $feedSource)
    {
        $secrets = $feedSource->getSecrets();

        if (null === $secrets) {
            throw new \RuntimeException('No secrets found for feed source.');
        }

        if (!isset($secrets['api_base_uri'], $secrets['client_id'], $secrets['client_secret'])) {
            throw new \RuntimeException('Missing required secrets for feed source.');
        }

        if (false === filter_var($secrets['api_base_uri'], FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid api_endpoint.');
        }

        $this->apiBaseUri = rtrim((string) $secrets['api_base_uri'], '/');
        $this->clientId = $secrets['client_id'];
        $this->clientSecret = $secrets['client_secret'];
    }
}
