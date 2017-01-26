<?php

namespace TestSuite\Pimcore\Cache\Adapter\SymfonyProxy;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Symfony\Component\Cache\Tests\Adapter\FilesystemAdapterTest;
use TestSuite\Pimcore\Cache\Factory;
use TestSuite\Pimcore\Cache\Pool\SymfonyProxy\Traits\SymfonyProxyTestTrait;

class FilesystemAdapterProxyTest extends FilesystemAdapterTest
{
    use SymfonyProxyTestTrait {
        createCachePool as _createCachePool;
    }

    public function createCachePool($defaultLifetime = 0)
    {
        $this->defaultLifetime = $defaultLifetime;

        return $this->_createCachePool();
    }

    /**
     * @return PimcoreCacheItemPoolInterface
     */
    protected function buildCachePool()
    {
        return (new Factory())->createFilesystemAdapterProxyItemPool($this->defaultLifetime);
    }
}
