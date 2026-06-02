<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\ScreenUser;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Enriches every log record with request and identity context.
 *
 * Field names here are the interim PR 1 names (request_id, route, method,
 * user_id, screen_id, tenant_id); they are renamed to OpenTelemetry semantic
 * conventions in a later change.
 */
final readonly class RequestContextProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getMainRequest();
        if (null !== $request) {
            // No format validation — accept whatever nginx/Traefik passed through.
            $record->extra['request_id'] = $request->attributes->get('_request_id')
                ?? $request->headers->get('X-Request-Id');
            $record->extra['route'] = $request->attributes->get('_route');
            $record->extra['method'] = $request->getMethod();
        }

        $user = $this->security->getUser();
        if (null !== $user) {
            // Screen tokens authenticate as ScreenUser; everything else is a
            // back-office User. Populate screen_id XOR user_id accordingly.
            if ($user instanceof ScreenUser) {
                $record->extra['screen_id'] = (string) $user->getScreen()->getId();
            } else {
                $record->extra['user_id'] = $user->getUserIdentifier();
            }

            if ($user instanceof TenantScopedUserInterface) {
                // getActiveTenant() can throw when no tenant is resolved yet;
                // enrichment must never break the request it is annotating.
                try {
                    $record->extra['tenant_id'] = $user->getActiveTenant()->getTenantKey();
                } catch (\Throwable) {
                    // No active tenant on this request — leave tenant_id unset.
                }
            }
        }

        return $record;
    }
}
