<?php

declare(strict_types=1);

namespace App\Feed\SourceType\Brnd;

use App\Entity\Tenant\FeedSource;

readonly class SecretsDTO
{
    public string $apiBaseUri;
    public string $apiAuthKey;
    public string $companyId;
    public string $apiVersion;

    public function __construct(FeedSource $feedSource)
    {
        $secrets = $feedSource->getSecrets();

        if (null === $secrets) {
            throw new \RuntimeException('No secrets found for feed source.');
        }

        if (!isset($secrets['api_base_uri'], $secrets['company_id'], $secrets['api_auth_key'])) {
            throw new \RuntimeException('Missing required secrets for feed source.');
        }

        if (false === filter_var($secrets['api_base_uri'], FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Invalid api_endpoint.');
        }

        $this->apiBaseUri = rtrim((string) $secrets['api_base_uri'], '/');
        $this->companyId = (string) $secrets['company_id'];
        $this->apiAuthKey = (string) $secrets['api_auth_key'];

        $version = $secrets['api_version'] ?? '1.0';

        if (!is_string($version) || 1 !== preg_match('/^\d+(\.\d+)?$/', $version)) {
            throw new \RuntimeException('Invalid api_version. Expected format like "1.0" or "2.0".');
        }

        $this->apiVersion = $version;
    }
}
