<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Tests\Cache\Factory;

/**
 * @group cache.core.array
 */
class ArrayCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * @return SymfonyAdapterProxy
     */
    protected function createCachePool()
    {
        return (new Factory())->createArrayAdapterProxyItemPool($this->defaultLifetime);
    }
}
