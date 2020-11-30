<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Tests\Util\TestHelper;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * @group cache.core.db
 */
class PdoCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     * @return PdoAdapter
     */
    protected function createCachePool()
    {
        TestHelper::checkDbSupport();
        $adapter = new PdoAdapter(\Pimcore::getContainer()->get('doctrine.dbal.default_connection'), '', $this->defaultLifetime);
        return $adapter;
    }
}
