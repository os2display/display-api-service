<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use App\Entity\Interfaces\TenantScopedUserInterface;
use App\Entity\ScreenUser;
use App\Logger\LogField;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Enriches every log record with request and identity context.
 *
 * Field names follow OpenTelemetry semantic conventions (http.request.method,
 * http.route, url.path, client.address, enduser.id, screen.id, tenant.key).
 * `request_id` is kept as-is. `client.address` is set to the raw client IP here
 * and truncated to a GDPR-safe form by {@see SensitiveDataProcessor}, which runs
 * after this processor.
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
            $record->extra[LogField::REQUEST_ID] = $request->attributes->get('_request_id')
                ?? $request->headers->get('X-Request-Id');
            // http.route is the matched route's declared path TEMPLATE, never the
            // concrete URL: a request to `GET /v2/screens/01HXYZ…` is logged as
            // `http.route = /v2/screens/{id}` — the entity id never appears in
            // this field (low-cardinality, GDPR-safe; OTel http.route). Only set
            // when a route matched. The concrete id-bearing path is logged
            // separately as url.path below.
            $routeTemplate = $this->routeTemplate($request);
            if (null !== $routeTemplate) {
                $record->extra[LogField::HTTP_ROUTE] = $routeTemplate;
            }
            $record->extra[LogField::HTTP_REQUEST_METHOD] = $request->getMethod();
            $record->extra[LogField::URL_PATH] = $request->getPathInfo();
            // Raw IP; SensitiveDataProcessor truncates it to a GDPR-safe form
            // (except for screen clients, which are outside GDPR — see that class).
            $record->extra[LogField::CLIENT_ADDRESS] = $request->getClientIp();
        }

        $user = $this->security->getUser();
        if (null !== $user) {
            // Enrichment must never break the request it is annotating. Every
            // identity accessor below can throw — getActiveTenant() when no tenant
            // is resolved yet, getScreen() on a not-yet-hydrated screen token,
            // getUserIdentifier() on a custom user — so the whole block is guarded.
            // Fields written before a throw are kept (the record is mutated in
            // place); the failing one and any after it are simply left unset.
            try {
                // Screen tokens authenticate as ScreenUser; everything else is a
                // back-office User. Populate screen.id XOR enduser.id accordingly.
                if ($user instanceof ScreenUser) {
                    $record->extra[LogField::SCREEN_ID] = (string) $user->getScreen()->getId();
                } else {
                    $record->extra[LogField::ENDUSER_ID] = $user->getUserIdentifier();
                }

                if ($user instanceof TenantScopedUserInterface) {
                    $record->extra[LogField::TENANT_KEY] = $user->getActiveTenant()->getTenantKey();
                }
            } catch (\Throwable) { // @phpstan-ignore logging.silentCatch (log enrichment must never break the request it annotates; identity accessors fail pre-resolution and simply omit the field)
                // An identity accessor failed (no active tenant, unhydrated screen,
                // lazy-load error, …). Keep whatever was set; never break logging.
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
