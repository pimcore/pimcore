<?php

namespace Pimcore\Tests\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;

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
