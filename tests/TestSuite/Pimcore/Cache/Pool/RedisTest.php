<?php

namespace TestSuite\Pimcore\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;
use TestSuite\Pimcore\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group Redis
 */
class RedisTest extends CachePoolTest
{
    use CacheItemPoolTestTrait;
    use RedisItemPoolTrait;
}
