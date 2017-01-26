<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use TestSuite\Pimcore\Cache\Factory;

class ArrayCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * @return SymfonyAdapterProxyCacheItemPool
     */
    protected function createCachePool()
    {
        return (new Factory())->createArrayAdapterProxyItemPool($this->defaultLifetime);
    }
}
