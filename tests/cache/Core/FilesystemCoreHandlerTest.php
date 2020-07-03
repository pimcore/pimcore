<?php

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Tests\Cache\Factory;

/**
 * @group cache.core.file
 */
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
