<?php

namespace Pimcore\Tests\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Tests\Cache\Factory;

trait RedisItemPoolTrait
{
    /**
     * @var array
     */
    protected $redisConnectionOptions = [];

    /**
     * @var array
     */
    protected $redisOptions = [];

    /**
     * @return PimcoreCacheItemPoolInterface|Redis
     */
    protected function buildCachePool()
    {
        return (new Factory())->createRedisItemPool($this->defaultLifetime, $this->redisConnectionOptions, $this->redisOptions);
    }
}
