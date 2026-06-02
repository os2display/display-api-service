<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\ScreenUser;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
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
            // The matched route's declared path TEMPLATE, not the concrete URL:
            // a request to `GET /v2/screens/01HXYZ…` is logged as
            // `route = /v2/screens/{id}` — the entity id never appears in this
            // field (low-cardinality, GDPR-safe; OTel `http.route` semantics).
            // Only set when a route matched; the concrete id-bearing path is not
            // logged here.
            $routeTemplate = $this->routeTemplate($request);
            if (null !== $routeTemplate) {
                $record->extra['route'] = $routeTemplate;
            }
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

    /**
     * The matched route's path template (OTel `http.route`), e.g.
     * `/v2/screens/{id}` — never the concrete path with the id. Reconstructed
     * from the request path by substituting the matched route parameters back to
     * their `{name}` placeholders, with the optional API Platform `.{_format}`
     * suffix stripped. Returns null when no route matched, so the field is only
     * present for matched requests (per OTel guidance).
     *
     * This deliberately avoids injecting the router: depending on it forms a
     * circular service graph with the `database` channel logger used by the DBAL
     * connection middleware (processor → router → EntityManager → DBAL middleware
     * → database logger → processor).
     */
    private function routeTemplate(Request $request): ?string
    {
        $routeName = $request->attributes->get('_route');
        if (!is_string($routeName)) {
            return null;
        }

        $params = $request->attributes->get('_route_params');
        if (!is_array($params)) {
            $params = [];
        }

        $path = $request->getPathInfo();
        foreach ($params as $name => $value) {
            // Skip framework params (_format, _locale, …); only real placeholders.
            if (!is_string($name) || str_starts_with($name, '_') || !is_scalar($value)) {
                continue;
            }
            $value = (string) $value;
            if ('' !== $value) {
                $path = str_replace($value, '{'.$name.'}', $path);
            }
        }

        // Strip the optional API Platform format suffix (e.g. `.jsonld`).
        $format = $params['_format'] ?? null;
        if (is_string($format) && '' !== $format) {
            $path = preg_replace('/\.'.preg_quote($format, '/').'$/', '', $path) ?? $path;
        }

        return $path;
    }
}
