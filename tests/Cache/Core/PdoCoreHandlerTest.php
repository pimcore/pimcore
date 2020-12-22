<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Tests\Util\TestHelper;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

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
        $pdoAdapter = new PdoAdapter(\Pimcore::getContainer()->get('doctrine.dbal.default_connection'), '', $this->defaultLifetime);
        $adapter = new TagAwareAdapter($pdoAdapter);

        return $adapter;
    }
}
