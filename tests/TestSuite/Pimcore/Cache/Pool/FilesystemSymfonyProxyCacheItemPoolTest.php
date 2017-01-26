<?php

namespace TestSuite\Pimcore\Cache\Adapter;

use Symfony\Component\Cache\Tests\Adapter\FilesystemAdapterTest;
use TestSuite\Pimcore\Cache\Pool\Traits\SymfonyProxy\FilesystemAdapterTrait;

class FilesystemSymfonyProxyCacheItemPoolTest extends FilesystemAdapterTest
{
    use FilesystemAdapterTrait {
        createCachePool as _createCachePool;
    }

    public function createCachePool($defaultLifetime = 0)
    {
        $this->defaultLifetime = $defaultLifetime;
        return $this->_createCachePool();
    }
}
