<?php

namespace Pimcore\Tests\Cache\Pool;

/**
 * @group cache.core.redis
 * @group cache.core.redis_lua
 */
class TaggableRedisLuaTest extends TaggableRedisTest
{
    protected function getRedisOptions(): array
    {
        return [
            'use_lua' => true,
        ];
    }
}
