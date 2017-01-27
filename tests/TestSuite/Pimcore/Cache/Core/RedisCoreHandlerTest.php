<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\Redis;
use TestSuite\Pimcore\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group Redis
 */
class RedisCoreHandlerTest extends AbstractCoreHandlerTest
{
    use RedisItemPoolTrait;

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
