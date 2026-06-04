<?php

// Read-only OPcache introspection, executed by an FPM worker so it sees the
// pool's shared memory (CLI PHP has its own, separate OPcache). Invoked from
// inside the container by the `opcache-status` wrapper (cgi-fcgi against the
// pool); see infrastructure/display-api-service/opcache-status.
//
// Web-served requests are refused: a webserver always passes REMOTE_ADDR,
// while the cgi-fcgi exec path does not, so a webserver (mis)configuration
// routing this script can never expose it.

declare(strict_types=1);

if ('' !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');

$status = function_exists('opcache_get_status') ? opcache_get_status(false) : false;

if (false === $status) {
    http_response_code(503);
    echo json_encode(['error' => 'OPcache is not enabled for this FPM pool'], JSON_THROW_ON_ERROR);
    exit;
}

echo json_encode(
    [
        'status' => $status,
        'configuration' => opcache_get_configuration(),
    ],
    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
);
