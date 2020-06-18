<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\Redis;
use Pimcore\Tests\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group cache.core.redis
 * @group cache.core.redis_lua
 */
class RedisLuaCoreHandlerTest extends AbstractCoreHandlerTest
{
    use RedisItemPoolTrait;

    protected function getRedisOptions(): array
    {
        return [
            'use_lua' => true,
        ];
    }

    /**
     * Initializes item pool
     *
     * @return Redis
     */
    protected function createCachePool()
    {
        return $this->buildCachePool();
    }
}
