<?php

namespace Pimcore\Tests\Cache\Core;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;

/**
 * @group cache.core.redis
 */
class RedisCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     * @return RedisTagAwareAdapter
     */
    protected function createCachePool()
    {
        $dsn = getenv('PIMCORE_TEST_REDIS_DSN');
        $client = RedisAdapter::createConnection($dsn);
        $adapter = new RedisTagAwareAdapter($client, '', $this->defaultLifetime);

        return $adapter;
    }
}
