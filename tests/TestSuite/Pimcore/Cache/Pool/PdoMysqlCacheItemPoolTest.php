<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use TestSuite\Pimcore\Cache\Factory;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;

class PdoMysqlCacheItemPoolTest extends CachePoolTest
{
    use CacheItemPoolTestTrait;

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        return (new Factory())->createPdoMysqlItemPool($this->defaultLifetime);
    }
}
