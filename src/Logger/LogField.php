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
    public const string REQUEST_ID = 'request_id';
    public const string HTTP_ROUTE = 'http.route';
    public const string HTTP_REQUEST_METHOD = 'http.request.method';
    public const string URL_PATH = 'url.path';
    public const string CLIENT_ADDRESS = 'client.address';
    public const string USER_ID = 'user.id';
    public const string SCREEN_ID = 'screen.id';
    public const string TENANT_KEY = 'tenant.key';
    public const string TRACE_ID = 'trace_id';
    public const string SPAN_ID = 'span_id';
}
