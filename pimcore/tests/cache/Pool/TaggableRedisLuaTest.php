<?php

namespace Pimcore\Tests\Cache\Pool;

/**
 * @group cache.core.redis
 * @group cache.core.redis_lua
 */
class TaggableRedisLuaTest extends TaggableRedisTest
{
    /**
     * @var array
     */
    protected $redisOptions = [
        'use_lua' => true
    ];
}
