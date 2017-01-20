<?php

namespace TestSuite\Pimcore\Cache\Core;

use Test\Cache\Traits\PdoMysqlAdapterTrait;

require_once __DIR__ . '/../../../../lib/Test/Cache/Traits/PdoMysqlAdapterTrait.php';

class PdoMysqlCoreHandlerTest extends AbstractCoreHandlerTest
{
    use PdoMysqlAdapterTrait;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::fetchPdo();
    }

    protected function setUpCacheAdapters()
    {
        $cacheAdapter = $this->createPdoAdapter();
        $cacheAdapter->setLogger(static::$logger);

        // make sure we start with a clean state
        $cacheAdapter->clear();

        $this->cacheAdapter = $cacheAdapter;
        $this->tagAdapter   = $cacheAdapter;
    }
}
