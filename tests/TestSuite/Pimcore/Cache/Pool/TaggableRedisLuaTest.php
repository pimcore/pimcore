<?php

namespace TestSuite\Pimcore\Cache\Pool;

/**
 * @group Redis
 * @group RedisLua
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
