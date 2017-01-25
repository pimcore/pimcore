<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\TaggableCachePoolTest;
use Psr\Cache\CacheItemPoolInterface;
use Test\Cache\Traits\PdoMysqlCacheItemPoolTrait;

abstract class TagPdoMysqlCacheItemPoolTest extends TaggableCachePoolTest
{
    use PdoMysqlCacheItemPoolTrait;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::fetchPdo();
    }

    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    public function createCachePool()
    {
        $itemPool = $this->createPdoItemPool();
        $itemPool->clear();

        return $itemPool;
    }
}
