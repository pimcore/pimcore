<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\Doctrine;
use Pimcore\Tests\Cache\Factory;

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
