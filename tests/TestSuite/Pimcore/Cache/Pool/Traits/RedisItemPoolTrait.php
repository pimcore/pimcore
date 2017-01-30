<?php

namespace TestSuite\Pimcore\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\Redis;
use TestSuite\Pimcore\Cache\Factory;

trait RedisItemPoolTrait
{
    /**
     * @var array
     */
    protected $redisOptions = [];

    /**
     * @return PimcoreCacheItemPoolInterface|Redis
     */
    protected function buildCachePool()
    {
        $redisDb = getenv('TEST_REDIS_DB');
        if (!$redisDb) {
            $this->markTestSkipped('TEST_REDIS_DB env var is not configured');
        }

        $connectionOptions = [
            'database' => $redisDb
        ];

        return (new Factory())->createRedisItemPool($this->defaultLifetime, $connectionOptions, $this->redisOptions);
    }
}
