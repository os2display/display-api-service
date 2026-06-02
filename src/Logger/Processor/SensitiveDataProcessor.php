<?php

declare(strict_types=1);

namespace App\Logger\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * GDPR/secret backstop. Runs after the context processors (lower priority) so it
 * can scrub what they add:
 *
 *  - `client.address` is truncated (IPv4: drop the last octet; IPv6: keep the
 *    /48, i.e. the first three hextets) so no full client IP is ever emitted.
 *  - Any key whose name looks secret-bearing (password, token, secret,
 *    authorization, api_key, …) is replaced with a redaction marker, anywhere
 *    in `context` or `extra`, at any nesting depth.
 *
 * This is a safety net, not a license: code must still avoid putting secrets in
 * log context in the first place.
 */
final class SensitiveDataProcessor implements ProcessorInterface
{
    private const REDACTED = '[redacted]';
    private const ADDRESS_KEY = 'client.address';

    /**
     * Case-insensitive substrings that mark a key as secret-bearing. Kept narrow
     * enough not to match legitimate keys such as `tenant.key`.
     *
     * @var list<string>
     */
    private const SECRET_KEY_FRAGMENTS = [
        'password', 'passwd', 'secret', 'authorization',
        'api_key', 'apikey', 'token', 'credential', 'bearer',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $this->scrub($record->extra);
        $context = $this->scrub($record->context);

        if (isset($extra[self::ADDRESS_KEY]) && is_string($extra[self::ADDRESS_KEY])) {
            $extra[self::ADDRESS_KEY] = $this->truncateAddress($extra[self::ADDRESS_KEY]);
        }

        return $record->with(context: $context, extra: $extra);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSecretKey($key)) {
                $data[$key] = self::REDACTED;
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }

    private function isSecretKey(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SECRET_KEY_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function truncateAddress(string $address): string
    {
        if (false !== filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $octets = explode('.', $address);
            $octets[3] = '0';

            return implode('.', $octets);
        }

        if (false !== filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Keep the /48 (first three hextets), zero the rest.
            $expanded = $this->expandIpv6($address);
            $hextets = explode(':', $expanded);
            for ($i = 3; $i < 8; ++$i) {
                $hextets[$i] = '0';
            }

            return implode(':', $hextets);
        }

        // Not a recognised IP — drop it rather than risk leaking an identifier.
        return self::REDACTED;
    }

    private function expandIpv6(string $address): string
    {
        $binary = inet_pton($address);
        if (false === $binary) {
            return $address;
        }

        $hextets = unpack('n8', $binary);

        return implode(':', array_map(dechex(...), $hextets ?: []));
    }
}
