<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\CachePoolTest;
use TestSuite\Pimcore\Cache\Pool\Traits\PdoMysqlCacheItemPoolTestTrait;

class PdoMysqlCacheItemPoolTest extends CachePoolTest
{
    use PdoMysqlCacheItemPoolTestTrait;
}
