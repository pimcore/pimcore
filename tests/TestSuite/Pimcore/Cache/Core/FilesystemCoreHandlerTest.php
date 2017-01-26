<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use TestSuite\Pimcore\Cache\Factory;

class FilesystemCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * @return SymfonyAdapterProxy
     */
    protected function createCachePool()
    {
        return (new Factory())->createFilesystemAdapterProxyItemPool($this->defaultLifetime);
    }
}
