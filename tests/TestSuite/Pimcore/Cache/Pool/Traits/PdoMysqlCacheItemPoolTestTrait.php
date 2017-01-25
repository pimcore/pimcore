<?php

namespace TestSuite\Pimcore\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PdoMysqlCacheItemPool;
use TestSuite\Pimcore\Cache\Traits\LogHandlerTrait;
use TestSuite\Pimcore\Cache\Traits\PdoMysqlCacheItemPoolTrait;

trait PdoMysqlCacheItemPoolTestTrait
{
    use LogHandlerTrait;
    use PdoMysqlCacheItemPoolTrait;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::setupLogger((new \ReflectionClass(__CLASS__))->getShortName());
        static::fetchPdo();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        static::handleLogOutput();
    }

    /**
     * @return PdoMysqlCacheItemPool that is used in the tests
     */
    public function createCachePool()
    {
        $itemPool = $this->createPdoItemPool();
        $itemPool->setLogger(static::$logger);

        return $itemPool;
    }
}
