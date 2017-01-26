<?php

namespace TestSuite\Pimcore\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;
use TestSuite\Pimcore\Cache\Pool\Traits\RedisItemPoolTrait;

class PdoMysqlTest extends CachePoolTest
{
    use CacheItemPoolTestTrait;
    use RedisItemPoolTrait;
}
