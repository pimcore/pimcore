<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\Redis;
use Pimcore\Tests\Cache\Pool\Traits\RedisItemPoolTrait;

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
