<?php

namespace Pimcore\Tests\Cache\Pool;

use Cache\IntegrationTests\CachePoolTest;
use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Tests\Cache\Factory;
use Pimcore\Tests\Cache\Pool\Traits\CacheItemPoolTestTrait;

/**
 * @group DB
 */
class DoctrineTest extends CachePoolTest
{
    use CacheItemPoolTestTrait;

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        return (new Factory())->createDoctrineItemPool($this->defaultLifetime);
    }
}
