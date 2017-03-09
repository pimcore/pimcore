<?php

namespace Pimcore\Tests\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;
use Pimcore\Tests\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group cache.core.redis
 */
class RedisTest extends CachePoolTest
{
    use CacheItemPoolTestTrait;
    use RedisItemPoolTrait;
}
