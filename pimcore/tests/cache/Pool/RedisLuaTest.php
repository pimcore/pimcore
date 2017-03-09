<?php

namespace Pimcore\Tests\Cache\Pool;

/**
 * @group cache.core.redis
 * @group cache.core.redis_lua
 */
class RedisLuaTest extends RedisTest
{
    /**
     * @var array
     */
    protected $redisOptions = [
        'use_lua' => true
    ];
}
