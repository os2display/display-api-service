<?php

declare(strict_types=1);

namespace App\NemDeling;

final class NemDelingConfig
{
    public function __construct(
        private readonly string $basicAuthUser = '',
        private readonly string $basicAuthPass = '',
        private readonly string $tenantKey = '',
        private readonly string $eventTemplateTitle = 'Event',
        private readonly string $eventListTemplateTitle = 'Event List',
    ) {}

    public function isBasicAuthEnabled(): bool
    {
        return '' !== $this->basicAuthUser && '' !== $this->basicAuthPass;
    }

    public function getBasicAuthUser(): string
    {
        return $this->basicAuthUser;
    }

    public function getBasicAuthPass(): string
    {
        return $this->basicAuthPass;
    }

    public function getTenantKey(): string
    {
        return $this->tenantKey;
    }

    public function getEventTemplateTitle(): string
    {
        return $this->eventTemplateTitle;
    }

    public function getEventListTemplateTitle(): string
    {
        return $this->eventListTemplateTitle;
    }
}
