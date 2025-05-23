<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Brnd;

use App\Entity\Tenant\FeedSource;

readonly class SecretsDTO
{
    public string $apiBaseUri;
    public string $apiAuthKey;

    public function __construct(FeedSource $feedSource)
    {
        $secrets = $feedSource->getSecrets();

        if (null === $secrets) {
            throw new \RuntimeException('No secrets found for feed source.');
        }

        if (!isset($secrets['api_base_uri'], $secrets['api_auth_key'])) {
            throw new \RuntimeException('Missing required secrets for feed source.');
        }

        if (false === filter_var($secrets['api_base_uri'], FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid api_endpoint.');
        }

        $this->apiBaseUri = rtrim((string) $secrets['api_base_uri'], '/');
        $this->apiAuthKey = $secrets['api_auth_key'];
    }
}
