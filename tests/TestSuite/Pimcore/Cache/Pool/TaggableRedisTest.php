<?php

namespace TestSuite\Pimcore\Cache\Pool;

use Cache\IntegrationTests\TaggableCachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;
use TestSuite\Pimcore\Cache\Pool\Traits\RedisItemPoolTrait;

class TaggableRedisTest extends TaggableCachePoolTest
{
    use CacheItemPoolTestTrait;
    use RedisItemPoolTrait;
}
