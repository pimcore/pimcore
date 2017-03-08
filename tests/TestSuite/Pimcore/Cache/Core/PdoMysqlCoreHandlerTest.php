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
        $db = getenv('TEST_MYSQL_DB');
        if (!$db) {
            $this->markTestSkipped('TEST_MYSQL_DB env var is not configured');
        }

        return (new Factory())->createPdoMysqlItemPool($this->defaultLifetime);
    }
}
