<?php

namespace TestSuite\Pimcore\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use TestSuite\Pimcore\Cache\Factory;
use TestSuite\Pimcore\Cache\Pool\Traits\CacheItemPoolTestTrait;

class DoctrineTest extends CachePoolTest
{
    use CacheItemPoolTestTrait;

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        $db = getenv('TEST_MYSQL_DB');
        if (!$db) {
            $this->markTestSkipped('TEST_MYSQL_DB env var is not configured');
        }

        return (new Factory())->createDoctrineItemPool($this->defaultLifetime);
    }
}
