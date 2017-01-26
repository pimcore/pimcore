<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\PdoMysqlCacheItemPool;
use TestSuite\Pimcore\Cache\Factory;

class PdoMysqlCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     * @return PdoMysqlCacheItemPool
     */
    protected function createCachePool()
    {
        return (new Factory())->createPdoMysqlItemPool($this->defaultLifetime);
    }
}
