<?php

namespace TestSuite\Pimcore\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use TestSuite\Pimcore\Cache\Factory;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;

class PdoMysqlTest extends CachePoolTest
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
