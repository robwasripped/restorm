<?php

declare(strict_types=1);

namespace Robwasripped\Restorm\Tests\Unit\Connection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Robwasripped\Restorm\Connection\ConnectionInterface;
use Robwasripped\Restorm\Connection\ConnectionRegister;

class ConnectionRegisterTest extends TestCase
{
    private ConnectionRegister $register;

    protected function setUp(): void
    {
        $this->register = new ConnectionRegister();
    }

    #[Test]
    public function getConnectionsReturnsSingleConnection(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $this->register->registerConnection('api', $connection);

        $connections = $this->register->getConnections('api');

        $this->assertCount(1, $connections);
        $this->assertSame($connection, $connections[0]);
    }

    #[Test]
    public function getConnectionsIncludesCacheConnectionFirst(): void
    {
        $apiConnection = $this->createMock(ConnectionInterface::class);
        $cacheConnection = $this->createMock(ConnectionInterface::class);

        $this->register->registerConnection('api', $apiConnection);
        $this->register->registerConnection('_cache', $cacheConnection);

        $connections = $this->register->getConnections('api');

        $this->assertCount(2, $connections);
        $this->assertSame($cacheConnection, $connections[0]);
        $this->assertSame($apiConnection, $connections[1]);
    }

    #[Test]
    public function getConnectionsDoesNotIncludeCacheForCacheRequest(): void
    {
        $cacheConnection = $this->createMock(ConnectionInterface::class);
        $this->register->registerConnection('_cache', $cacheConnection);

        $connections = $this->register->getConnections('_cache');

        // _cache is in connections array AND is the requested connection,
        // so it appears as the cache entry AND as the named connection.
        $this->assertCount(2, $connections);
    }

    #[Test]
    public function registeringConnectionOverwritesPrevious(): void
    {
        $first = $this->createMock(ConnectionInterface::class);
        $second = $this->createMock(ConnectionInterface::class);

        $this->register->registerConnection('api', $first);
        $this->register->registerConnection('api', $second);

        $connections = $this->register->getConnections('api');

        $this->assertSame($second, $connections[0]);
    }
}
