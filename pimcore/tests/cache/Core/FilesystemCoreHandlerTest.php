<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Tests\Cache\Factory;

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
