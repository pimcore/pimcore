<?php

namespace Pimcore\Tests\Cache\Pool;

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
