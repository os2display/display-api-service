<?php

declare(strict_types=1);

namespace App\Tests\Cache;

use App\Cache\CacheAdapterFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheAdapterFactoryTest extends TestCase
{
    public function testRedisAdapterIsCreatedWithoutConnecting(): void
    {
        // The DSN points at nothing — creation must succeed because the
        // connection is lazy and only opened on first cache access.
        $factory = $this->createFactory('redis');
        $this->assertInstanceOf(RedisAdapter::class, $factory->create('some.pool', 300));
    }

    public function testFilesystemAdapterIsCreated(): void
    {
        $factory = $this->createFactory('filesystem');
        $adapter = $factory->create('some.pool', 300);
        $this->assertInstanceOf(FilesystemAdapter::class, $adapter);

        $item = $adapter->getItem('key');
        $adapter->save($item->set('value'));
        $this->assertSame('value', $adapter->getItem('key')->get());
    }

    public function testMemcachedAdapterIsCreatedWithoutConnecting(): void
    {
        if (!MemcachedAdapter::isSupported()) {
            $this->markTestSkipped('ext-memcached is not enabled.');
        }

        // The DSN points at nothing — creation must succeed because the
        // Memcached client only connects on first cache access.
        $factory = $this->createFactory('memcached');
        $this->assertInstanceOf(MemcachedAdapter::class, $factory->create('some.pool', 300));
    }

    public function testApcuAdapterIsCreated(): void
    {
        if (!ApcuAdapter::isSupported()) {
            $this->markTestSkipped('APCu is not enabled.');
        }

        $factory = $this->createFactory('apcu');
        $this->assertInstanceOf(ApcuAdapter::class, $factory->create('some.pool', 300));
    }

    public function testDbalAdapterIsCreatedAndCreatesItsTableOnFirstUse(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not enabled.');
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $factory = $this->createFactory('dbal', $connection);
        $adapter = $factory->create('some.pool', 300);
        $this->assertInstanceOf(DoctrineDbalAdapter::class, $adapter);

        $item = $adapter->getItem('key');
        $adapter->save($item->set('value'));
        $this->assertSame('value', $adapter->getItem('key')->get());
    }

    public function testDbalAdapterRequiresAConnection(): void
    {
        $factory = $this->createFactory('dbal');

        $this->expectException(\LogicException::class);
        $factory->create('some.pool', 300);
    }

    public function testUnknownAdapterIsRejected(): void
    {
        $factory = $this->createFactory('bogus');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown CACHE_ADAPTER "bogus"');
        $factory->create('some.pool', 300);
    }

    private function createFactory(string $adapter, ?Connection $dbalConnection = null): CacheAdapterFactory
    {
        return new CacheAdapterFactory(
            $adapter,
            'redis://localhost.invalid:6379/0',
            'memcached://localhost.invalid:11211',
            sys_get_temp_dir().'/cache-adapter-factory-test',
            $dbalConnection,
        );
    }
}
