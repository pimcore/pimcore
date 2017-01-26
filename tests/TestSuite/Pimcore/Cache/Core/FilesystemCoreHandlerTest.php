<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class FilesystemCoreHandlerTest extends AbstractCoreHandlerTest
{
    protected function setupItemPool()
    {
        $filesystemAdapter = new FilesystemAdapter('', 3600);
        $tagAdapter        = new TagAwareAdapter($filesystemAdapter);

        $itemPool = new SymfonyAdapterProxyCacheItemPool($tagAdapter);
        $itemPool->setLogger(static::$logger);

        // make sure we start with a clean state
        $itemPool->clear();

        $this->itemPool = $itemPool;
    }
}
