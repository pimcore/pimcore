<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;
use Test\Cache\Traits\PdoMysqlCacheItemPoolTrait;

class PdoMysqlCacheItemPoolTest extends CachePoolTest
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
