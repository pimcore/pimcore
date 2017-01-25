<?php

namespace TestSuite\Pimcore\Cache\Core;

use Test\Cache\Traits\PdoMysqlCacheItemPoolTrait;

class PdoMysqlCoreHandlerTest extends AbstractCoreHandlerTest
{
    use PdoMysqlCacheItemPoolTrait;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::fetchPdo();
    }

    protected function setupItemPool()
    {
        $itemPool = $this->createPdoItemPool();
        $itemPool->setLogger(static::$logger);

        // make sure we start with a clean state
        $itemPool->clear();

        $this->itemPool = $itemPool;
    }
}
