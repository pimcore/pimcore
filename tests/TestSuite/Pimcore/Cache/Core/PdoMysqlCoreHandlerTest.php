<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\PdoMysql;
use TestSuite\Pimcore\Cache\Factory;

class PdoMysqlCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     * @return PdoMysql
     */
    protected function createCachePool()
    {
        return (new Factory())->createPdoMysqlItemPool($this->defaultLifetime);
    }
}
