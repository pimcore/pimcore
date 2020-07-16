<?php

namespace Pimcore\Tests\Cache\Pool;

/**
 * @group cache.core.redis
 * @group cache.core.redis_lua
 */
class RedisLuaTest extends RedisTest
{
    protected function getRedisOptions(): array
    {
        return [
            'use_lua' => true,
        ];
    }
}
