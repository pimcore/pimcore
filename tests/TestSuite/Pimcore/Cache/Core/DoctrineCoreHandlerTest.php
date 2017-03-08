<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\Doctrine;
use TestSuite\Pimcore\Cache\Factory;

class DoctrineCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     * @return Doctrine
     */
    protected function createCachePool()
    {
        $db = getenv('TEST_MYSQL_DB');
        if (!$db) {
            $this->markTestSkipped('TEST_MYSQL_DB env var is not configured');
        }

        return (new Factory())->createDoctrineItemPool($this->defaultLifetime);
    }
}
