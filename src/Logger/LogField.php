<?php

declare(strict_types=1);

namespace App\Logger;

/**
 * Canonical names for the structured log-record fields this application adds to
 * the Monolog `extra` array, following OpenTelemetry semantic conventions
 * (ADR 011 / docs/logging.md).
 *
 * Single source of truth so a producer (e.g. {@see Processor\RequestContextProcessor})
 * and a consumer (e.g. {@see Processor\SensitiveDataProcessor}, which keys its
 * client-IP truncation off SCREEN_ID) can never drift out of agreement on a
 * field name.
 */
final class LogField
{
    public const REQUEST_ID = 'request_id';
    public const HTTP_ROUTE = 'http.route';
    public const HTTP_REQUEST_METHOD = 'http.request.method';
    public const URL_PATH = 'url.path';
    public const CLIENT_ADDRESS = 'client.address';
    public const USER_ID = 'user.id';
    public const SCREEN_ID = 'screen.id';
    public const TENANT_KEY = 'tenant.key';
    public const TRACE_ID = 'trace_id';
    public const SPAN_ID = 'span_id';
}
