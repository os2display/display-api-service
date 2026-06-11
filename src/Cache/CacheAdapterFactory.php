<?php

declare(strict_types=1);

namespace App\Cache;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Builds cache pool adapters for the backend selected with the CACHE_ADAPTER env var.
 *
 * Symfony resolves a pool's `adapter:` option to a concrete service id when the container
 * is compiled, so the backend cannot be picked with a runtime `%env()%` placeholder in
 * cache.yaml. Instead the pools extend the abstract `app.cache.adapter` service (defined
 * in config/services.yaml), which delegates to this factory at runtime. Symfony's
 * CachePoolPass injects each pool's computed namespace and configured default lifetime
 * as the factory arguments.
 */
final readonly class CacheAdapterFactory
{
    public const ADAPTERS = ['redis', 'memcached', 'dbal', 'filesystem', 'apcu'];

    public function __construct(
        private string $adapter,
        private string $redisDsn,
        private string $memcachedDsn,
        private string $filesystemDirectory,
        private ?Connection $dbalConnection = null,
        private ?LoggerInterface $logger = null,
    ) {}

    public function create(string $namespace = '', int $defaultLifetime = 0): AdapterInterface
    {
        $adapter = match ($this->adapter) {
            // 'lazy' defers the connection until first use, matching how Symfony wires
            // DSN-based providers (pools must be instantiable without a reachable Redis).
            'redis' => new RedisAdapter(RedisAdapter::createConnection($this->redisDsn, ['lazy' => true]), $namespace, $defaultLifetime),
            // Requires ext-memcached (createConnection fails fast without it); the
            // Memcached client itself only connects on first use.
            'memcached' => new MemcachedAdapter(MemcachedAdapter::createConnection($this->memcachedDsn), $namespace, $defaultLifetime),
            // Reuses the application's Doctrine connection; the cache_items table is
            // created automatically on first write (see also the schema_filter in
            // config/packages/doctrine.yaml that hides it from the schema tools).
            'dbal' => new DoctrineDbalAdapter($this->dbalConnection ?? throw new \LogicException('CACHE_ADAPTER "dbal" requires a Doctrine DBAL connection.'), $namespace, $defaultLifetime),
            'filesystem' => new FilesystemAdapter($namespace, $defaultLifetime, $this->filesystemDirectory),
            'apcu' => new ApcuAdapter($namespace, $defaultLifetime),
            default => throw new \InvalidArgumentException(sprintf('Unknown CACHE_ADAPTER "%s". Expected one of: "%s".', $this->adapter, implode('", "', self::ADAPTERS))),
        };

        if (null !== $this->logger) {
            $adapter->setLogger($this->logger);
        }

        return $adapter;
    }
}
