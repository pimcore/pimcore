<?php

namespace TestSuite\Pimcore\Cache\Pool;

/**
 * @group Redis
 * @group RedisLua
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
